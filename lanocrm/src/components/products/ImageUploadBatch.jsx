import React, { useState, useCallback } from 'react';
import { Button, Upload, Card, Space, Tooltip, Progress, App } from 'antd';
import { UploadOutlined, DeleteOutlined } from '@ant-design/icons';
import styles from './ImageUploadBatch.module.css';
import { uploadMultipleProductImages, uploadMultipleVariantImages } from '../../api/productApi';

const ImageUploadBatch = ({ productId, variantId = null, onUploadSuccess, maxFiles = 10 }) => {
  const [fileList, setFileList] = useState([]);
  const [uploading, setUploading] = useState(false);
  const [uploadProgress, setUploadProgress] = useState(0);
  const { message } = App.useApp();

  const beforeUpload = useCallback(
    (file) => {
      const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
      const maxSize = 5 * 1024 * 1024; // 5MB

      if (!validTypes.includes(file.type)) {
        message.error(`${file.name}: Chỉ hỗ trợ JPG, PNG, GIF, WEBP`);
        return false;
      }
      if (file.size > maxSize) {
        message.error(`${file.name}: Kích thước tối đa 5MB`);
        return false;
      }
      if (fileList.length >= maxFiles) {
        message.warning(`Chỉ upload tối đa ${maxFiles} ảnh mỗi lần`);
        return false;
      }
      const reader = new FileReader();
      reader.onload = (e) => {
        setFileList((prev) => [
          ...prev,
          {
            uid: file.uid,
            name: file.name,
            file: file,
            preview: e.target.result,
            status: 'ready',
          },
        ]);
      };
      reader.readAsDataURL(file);
      return false; // Prevent auto upload
    },
    [fileList.length, maxFiles]
  );

  const handleRemove = useCallback((uid) => {
    setFileList((prev) => prev.filter((item) => item.uid !== uid));
  }, []);

  const handleClearAll = useCallback(() => {
    setFileList([]);
    setUploadProgress(0);
  }, []);

  const handleUploadAll = useCallback(async () => {
    if (fileList.length === 0) {
      message.warning('Chưa có ảnh nào để upload');
      return;
    }

    try {
      setUploading(true);
      setUploadProgress(0);

      const files = fileList.map((item) => item.file);

      let result;
      if (variantId) {
        result = await uploadMultipleVariantImages(files, variantId);
      } else if (productId) {
        result = await uploadMultipleProductImages(files, productId);
      } else {
        throw new Error('Không có productId hoặc variantId để upload ảnh');
      }

      setUploadProgress(100);

      if (result.success) {
        message.success(`✅ Upload thành công ${result.uploaded_count} ảnh`);
        if (result.failed_count > 0) {
          message.warning(`⚠️ ${result.failed_count} ảnh thất bại`);
        }
        setFileList([]);
        setUploadProgress(0);
        // Normal hóa dữ liệu trả về
        const fallbackImages = fileList.map((item) => ({
          id: Date.now() + Math.random(),
          image_url: item.preview,
          file_name: item.name,
          is_primary: 0,
        }));

        let normalizedFromApi = null;
        if (Array.isArray(result.data) && result.data.length > 0) {
          normalizedFromApi = result.data;
        } else if (Array.isArray(result.data?.files)) {
          normalizedFromApi = result.data.files.map((file, idx) => ({
            id: result.data?.ids?.[idx] || Date.now() + idx,
            image_url: file,
            file_name: typeof file === 'string' ? file.split('/').pop() : `Image-${idx + 1}`,
            is_primary: idx === 0 ? 1 : 0,
          }));
        }

        const imagesToReturn = normalizedFromApi && normalizedFromApi.length > 0 ? normalizedFromApi : fallbackImages;

        if (onUploadSuccess) onUploadSuccess(imagesToReturn);
      } else {
        message.error(result.message || 'Upload thất bại');
      }
    } catch (error) {
      console.error('Upload error:', error);
      message.error(error.message || 'Lỗi upload ảnh');
    } finally {
      setUploading(false);
    }
  }, [fileList, productId, variantId, onUploadSuccess]);

  return (
    <div className={styles.container}>
      <Space direction="vertical" style={{ width: '100%' }} size="large">
        <Upload
          multiple
          accept="image/*"
          beforeUpload={beforeUpload}
          showUploadList={false}
          disabled={uploading || fileList.length >= maxFiles}
        >
          <Button
            icon={<UploadOutlined />}
            size="large"
            block
            disabled={uploading || fileList.length >= maxFiles}
          >
            📂 Chọn ảnh để upload ({fileList.length}/{maxFiles})
          </Button>
        </Upload>

        {uploading && (
          <Progress
            percent={uploadProgress}
            status="active"
            strokeColor={{ from: '#108ee9', to: '#87d068' }}
          />
        )}

        {fileList.length > 0 && (
          <div className={styles.previewGrid}>
            {fileList.map((item) => (
              <Card
                key={item.uid}
                hoverable
                className={styles.previewCard}
                cover={
                  <div className={styles.previewImageWrapper}>
                    <img src={item.preview} alt={item.name} />
                    <div className={styles.previewOverlay}>
                      <Tooltip title="Xóa">
                        <Button
                          type="text"
                          danger
                          icon={<DeleteOutlined />}
                          onClick={() => handleRemove(item.uid)}
                          disabled={uploading}
                        />
                      </Tooltip>
                    </div>
                  </div>
                }
              >
                <Card.Meta
                  title={
                    <Tooltip title={item.name}>
                      <span className={styles.fileName}>{item.name}</span>
                    </Tooltip>
                  }
                  description={`${(item.file.size / 1024).toFixed(1)} KB`}
                />
              </Card>
            ))}
          </div>
        )}

        {fileList.length > 0 && (
          <Space style={{ width: '100%', justifyContent: 'flex-end' }}>
            <Button onClick={handleClearAll} disabled={uploading}>
              🗑️ Xóa tất cả
            </Button>
            <Button
              type="primary"
              onClick={handleUploadAll}
              loading={uploading}
              disabled={fileList.length === 0}
            >
              ⬆️ Upload {fileList.length} ảnh
            </Button>
          </Space>
        )}
      </Space>
    </div>
  );
};

export default ImageUploadBatch;
