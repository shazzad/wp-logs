// src/components/BulkActions.js
import React from "react";

const BulkActions = ({
  selectedCount,
  isLoading,
  isDeleting,
  hasLogs,
  onDeleteSelected,
  onDeleteAll,
}) => {
  return (
    <div className="swpl__bulk__actions">
      <div className="swpl__bulk__actions--info">
        {selectedCount > 0 && <span>{selectedCount} log(s) selected</span>}
      </div>
      <div className="swpl__bulk__actions--buttons">
        <button
          className="button"
          onClick={onDeleteSelected}
          disabled={isLoading || isDeleting || selectedCount === 0}
        >
          Delete Selected
        </button>
        <button
          className="button button-link-delete"
          onClick={onDeleteAll}
          disabled={isLoading || isDeleting || !hasLogs}
        >
          Delete all
        </button>
      </div>
    </div>
  );
};

export default BulkActions;
