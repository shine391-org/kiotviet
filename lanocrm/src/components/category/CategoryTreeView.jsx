import React from 'react';

const CategoryTreeView = ({ tree, onSelect, selectedId }) => {
  if (!tree || tree.length === 0) return null;

  const renderTree = (nodes) => {
    return (
      <ul>
        {nodes.map((node) => (
          <li key={node.id}>
            <span
              style={{ cursor: 'pointer', fontWeight: node.id === selectedId ? 'bold' : 'normal' }}
              onClick={() => onSelect(node.id)}
            >
              {node.name}
            </span>
            {node.children && node.children.length > 0 && renderTree(node.children)}
          </li>
        ))}
      </ul>
    );
  };

  return <div>{renderTree(tree)}</div>;
};

export default CategoryTreeView;