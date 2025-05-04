// src/components/LogsDeleteConfirmationModal.js
import React from "react";
import { Spinner } from "@wordpress/components";

const LogsDeleteConfirmationModal = ({
  type,
  selectedCount,
  isDeleting,
  onCancel,
  onConfirm,
}) => {
  return (
    <div className="swpl__modal__overlay">
      <div className="swpl-confirmation-modal">
        <div className="swpl-modal-header">
          <h2>Confirm Deletion</h2>
        </div>
        <div className="swpl__modal__body">
          {type === "all" ? (
            <p>
              Are you sure you want to delete ALL logs? This action cannot be
              undone.
            </p>
          ) : (
            <p>
              Are you sure you want to delete {selectedCount} selected log(s)?
              This action cannot be undone.
            </p>
          )}
        </div>
        <div className="swpl-modal-footer">
          <button className="button" onClick={onCancel} disabled={isDeleting}>
            Cancel
          </button>
          <button
            className="button button-primary button-link-delete"
            onClick={onConfirm}
            disabled={isDeleting}
          >
            {isDeleting ? (
              <>
                <Spinner />
                Deleting...
              </>
            ) : (
              "Delete"
            )}
          </button>
        </div>
      </div>
    </div>
  );
};

export default LogsDeleteConfirmationModal;
