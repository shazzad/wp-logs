// src/components/RequestDetailsModal.js
import React, { useState, useEffect } from "react";
import apiFetch from "@wordpress/api-fetch";
import { Spinner, TabPanel } from "@wordpress/components";

const RequestDetailsModal = ({ requestId, onClose }) => {
  const [requestDetails, setRequestDetails] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (requestId) {
      fetchDetails(requestId);
    }
  }, [requestId]);

  const fetchDetails = async (id) => {
    try {
      setIsLoading(true);
      const response = await apiFetch({
        path: `/wp/v2/requests/${id}`,
      });

      if (response && response.data) {
        setRequestDetails(response.data);
      } else {
        setError("Failed to retrieve request details");
      }
    } catch (err) {
      console.error("Error fetching request details:", err);
      setError("Failed to fetch request details. Please try again later.");
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
          <h2>Request Details</h2>
          <button className="swpl-modal-close" onClick={onClose}>
            &times;
          </button>
        </div>

        <div className="swpl__modal__body">
          {isLoading ? (
            <div className="swpl-loading">
              <Spinner />
              <p>Loading request details...</p>
            </div>
          ) : error ? (
            <div className="notice notice-error">
              <p>{error}</p>
            </div>
          ) : requestDetails ? (
            <div className="swpl__modal__content__rows">
              {[
                {
                  name: "Date",
                  value: new Date(requestDetails.date).toLocaleString(),
                },
                {
                  name: "Method",
                  value: (
                    <span
                      className={`request__method request__method--${requestDetails.request_method.toLowerCase()}`}
                    >
                      {requestDetails.request_method}
                    </span>
                  ),
                },
                { name: "URL", value: requestDetails.request_url },
                { name: "Status", value: requestDetails.response_code },
                { name: "ID", value: requestDetails.id },
              ].map((item, index) => (
                <div className="swpl__modal__meta" key={index}>
                  <div className="swpl__modal__meta--name">{item.name}:</div>
                  <div className="swpl__modal__meta--value">{item.value}</div>
                </div>
              ))}

              <TabPanel
                className="swpl-request-tabpanel"
                activeClass="swpl__tab--active"
                tabs={[
                  { name: "payload", title: "Payload" },
                  { name: "headers", title: "Headers" },
                ]}
              >
                {(tab) => (
                  <div className="swpl-request-detail-item">
                    <pre className="swpl__print">
                      {tab.name === "payload"
                        ? formatContext(requestDetails.request_payload)
                        : formatContext(requestDetails.request_headers)}
                    </pre>
                  </div>
                )}
              </TabPanel>

              <TabPanel
                className="swpl-response-tabpanel"
                activeClass="swpl__tab--active"
                tabs={[
                  { name: "data", title: "Response" },
                  { name: "headers", title: "Headers" },
                ]}
              >
                {(tab) => (
                  <div className="swpl-request-detail-item">
                    <pre className="swpl__print">
                      {tab.name === "data"
                        ? formatContext(requestDetails.response_data)
                        : formatContext(requestDetails.response_headers)}
                    </pre>
                  </div>
                )}
              </TabPanel>
            </div>
          ) : (
            <p>No request details available</p>
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
