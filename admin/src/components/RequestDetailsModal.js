// src/components/RequestDetailsModal.js
import React, { useState, useEffect } from "react";
import apiFetch from "@wordpress/api-fetch";
import { Spinner } from "@wordpress/components";

const RequestDetailsModal = ({ logId, onClose }) => {
  const [logDetails, setLogDetails] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (logId) {
      fetchDetails(logId);
    }
  }, [logId]);

  const fetchDetails = async (id) => {
    try {
      setIsLoading(true);
      const response = await apiFetch({
        path: `/wp/v2/requests/${id}`,
      });

      if (response && response.data) {
        setLogDetails(response.data);
      } else {
        setError("Failed to retrieve log details");
      }
    } catch (err) {
      console.error("Error fetching log details:", err);
      setError("Failed to fetch log details. Please try again later.");
    } finally {
      setIsLoading(false);
    }
  };

  const formatContext = (context) => {
    if (!context) return "";

    try {
      return JSON.stringify(context, null, 2);
    } catch (e) {
      return "Unable to display context";
    }
  };

  return (
    <div className="swpl-modal-overlay" onClick={onClose}>
      <div className="swpl-modal-content" onClick={(e) => e.stopPropagation()}>
        <div className="swpl-modal-header">
          <h2>Request Details</h2>
          <button className="swpl-modal-close" onClick={onClose}>
            &times;
          </button>
        </div>

        <div className="swpl-modal-body">
          {isLoading ? (
            <div className="swpl-loading">
              <Spinner />
              <p>Loading log details...</p>
            </div>
          ) : error ? (
            <div className="notice notice-error">
              <p>{error}</p>
            </div>
          ) : logDetails ? (
            <div className="swpl-log-details">
              <div className="swpl-log-detail-item">
                <strong>ID:</strong> {logDetails.id}
              </div>
              <div className="swpl-log-detail-item">
                <strong>Date:</strong>{" "}
                {new Date(logDetails.date).toLocaleString()}
              </div>
              <div className="swpl-log-detail-item">
                <strong>Url:</strong>
                <span>{logDetails.request_url}</span>
              </div>
              <div className="swpl-log-detail-item">
                <strong>Method:</strong> {logDetails.request_method}
              </div>
              <div className="swpl-log-detail-item">
                <strong>Status:</strong> {logDetails.response_code}
              </div>
              <div className="swpl-log-detail-item">
                <strong>Response Data:</strong>
                <pre className="swpl-log-context">
                  {formatContext(logDetails.response_data)}
                </pre>
              </div>
              <div className="swpl-log-detail-item">
                <strong>Response Headers:</strong>
                <pre className="swpl-log-context">
                  {formatContext(logDetails.response_headers)}
                </pre>
              </div>
              <div className="swpl-log-detail-item">
                <strong>Request Payload:</strong>
                <pre className="swpl-log-context">
                  {formatContext(logDetails.request_payload)}
                </pre>
              </div>
              <div className="swpl-log-detail-item">
                <strong>Request Headers:</strong>
                <pre className="swpl-log-context">
                  {formatContext(logDetails.request_headers)}
                </pre>
              </div>
            </div>
          ) : (
            <p>No log details available</p>
          )}
        </div>

        <div className="swpl-modal-footer">
          <button className="button" onClick={onClose}>
            Close
          </button>
        </div>
      </div>
    </div>
  );
};

export default RequestDetailsModal;
