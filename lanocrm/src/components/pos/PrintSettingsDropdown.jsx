import React, { useState } from 'react';
import { Button, Switch, InputNumber, Select, Popover } from 'antd';
import styles from './PrintSettingsDropdown.module.css';

const PrintSettingsDropdown = ({ children }) => {
    const [autoprint, setAutoprint] = useState(true);
    const [groupSameType, setGroupSameType] = useState(false);
    const [printCopies, setPrintCopies] = useState(1);
    const [printTemplate, setPrintTemplate] = useState('invoice');
    const [autoprintWarranty, setAutoprintWarranty] = useState(true);
    const [warrantyPer, setWarrantyPer] = useState('product');
    const [warrantyCopies, setWarrantyCopies] = useState(1);

    const content = (
        <div className={styles.content}>
            {/* Auto print invoice */}
            <div className={styles.row}>
                <span className={styles.label}>Tự động in hóa đơn</span>
                <Switch checked={autoprint} onChange={setAutoprint} />
            </div>

            {/* Group same type */}
            <div className={styles.row}>
                <span className={styles.label}>Gộp hàng cùng loại</span>
                <Switch checked={groupSameType} onChange={setGroupSameType} />
            </div>

            {/* Print copies */}
            <div className={styles.row}>
                <span className={styles.label}>Số bản in (Liên)</span>
                <InputNumber
                    min={1}
                    max={5}
                    value={printCopies}
                    onChange={setPrintCopies}
                    className={styles.numberInput}
                />
            </div>

            {/* Print template */}
            <div className={styles.templateSection}>
                <span className={styles.label}>Chọn mẫu in</span>
                <div className={styles.templateButtons}>
                    <Button
                        type={printTemplate === 'invoice' ? 'primary' : 'default'}
                        onClick={() => setPrintTemplate('invoice')}
                        className={styles.templateBtn}
                    >
                        A. Mẫu in hóa đơn
                    </Button>
                    <Button
                        type={printTemplate === 'warranty' ? 'primary' : 'default'}
                        onClick={() => setPrintTemplate('warranty')}
                        className={styles.templateBtn}
                    >
                        B. Bảo hành
                    </Button>
                </div>
            </div>

            {/* Auto print warranty */}
            <div className={styles.row}>
                <span className={styles.label}>Tự động in phiếu bảo hành</span>
                <Switch checked={autoprintWarranty} onChange={setAutoprintWarranty} />
            </div>

            {/* Warranty per */}
            <div className={styles.row}>
                <span className={styles.label}>Cho mỗi hàng hóa</span>
                <Select
                    value={warrantyPer}
                    onChange={setWarrantyPer}
                    options={[
                        { value: 'product', label: 'Cho mỗi hàng hóa' },
                        { value: 'invoice', label: 'Cho mỗi hóa đơn' },
                    ]}
                    className={styles.select}
                />
            </div>

            {/* Warranty copies */}
            <div className={styles.row}>
                <span className={styles.label}>Số bản in (Liên)</span>
                <InputNumber
                    min={1}
                    max={5}
                    value={warrantyCopies}
                    onChange={setWarrantyCopies}
                    className={styles.numberInput}
                />
            </div>

            {/* Action buttons */}
            <div className={styles.actions}>
                <Button className={styles.skipBtn}>Bỏ qua</Button>
                <Button type="primary" className={styles.doneBtn}>Xong</Button>
            </div>
        </div>
    );

    return (
        <Popover
            content={content}
            trigger="click"
            placement="bottomRight"
            overlayClassName={styles.popover}
        >
            {children}
        </Popover>
    );
};

export default PrintSettingsDropdown;
