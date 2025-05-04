// src/components/LogDetailsModal.js
import React, { useState, useEffect } from "react";
import apiFetch from "@wordpress/api-fetch";
import { Spinner } from "@wordpress/components";

const LogDetailsModal = ({ logId, onClose }) => {
  const [logDetails, setLogDetails] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (logId) {
      fetchLogDetails(logId);
    }
  }, [logId]);

  const fetchLogDetails = async (id) => {
    try {
      setIsLoading(true);
      const response = await apiFetch({
        path: `/wp/v2/logs/${id}`,
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
    <div className="swpl__modal__overlay" onClick={onClose}>
      <div
        className="swpl__modal__content"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="swpl-modal-header">
          <h2>Log Details</h2>
          <button className="swpl-modal-close" onClick={onClose}>
            &times;
          </button>
        </div>

        <div className="swpl__modal__body">
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
            <div className="swpl__modal__content__rows">
              {[
                {
                  name: "Date",
                  value: new Date(logDetails.date).toLocaleString(),
                },
                {
                  name: "Level",
                  value: (
                    <span
                      className={`log__level log__level--${logDetails.level}`}
                    >
                      {logDetails.level}
                    </span>
                  ),
                },
                { name: "Source", value: logDetails.source },
                { name: "Message", value: logDetails.message },
                { name: "ID", value: logDetails.id },
              ].map((item, index) => (
                <div className="swpl__modal__meta" key={index}>
                  <div className="swpl__modal__meta--name">{item.name}:</div>
                  <div className="swpl__modal__meta--value">{item.value}</div>
                </div>
              ))}

              <div className="swpl-log-detail-item">
                <strong>Data:</strong>
                <pre className="swpl-log-context">
                  {formatContext(logDetails.context)}
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

export default LogDetailsModal;
