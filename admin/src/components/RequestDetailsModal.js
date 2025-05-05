// src/components/RequestDetailsModal.js
import React, { useState, useEffect } from "react";
import ReactJsonView from "@microlink/react-json-view";
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
        path: `/swpl/v1/requests/${id}`,
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

  const jsonView = (data) => {
    if (!data) return "";

    // check if valid JSON
    if (typeof data === "string") {
      try {
        data = JSON.parse(data);
      } catch (e) {
        return <pre className="swpl__data--preview">{data}</pre>;
      }
    }

    try {
      // JSON.stringify(data, null, 2);
      return (
        <ReactJsonView
          src={data}
          name={null}
          enableClipboard={false}
          displayDataTypes={false}
          displayObjectSize={false}
        />
      );
    } catch (e) {
      return "Unable to display data";
    }
  };

  return (
    <div className="swpl__modal__overlay" onClick={onClose}>
      <div
        className="swpl__modal__content"
        onClick={(e) => e.stopPropagation()}
      >
        <div className="swpl__modal--header">
          <h2>Request Details</h2>
          <button className="swpl__modal--close-btn" onClick={onClose}>
            &times;
          </button>
        </div>

        <div className="swpl__modal--body">
          {isLoading ? (
            <div className="swpl__loading">
              <Spinner />
              <p>Loading request details...</p>
            </div>
          ) : error ? (
            <div className="notice notice-error">
              <p>{error}</p>
            </div>
          ) : requestDetails ? (
            <div className="swpl__modal--content-rows">
              {[
                {
                  name: "Date",
                  value: new Date(requestDetails.date_created).toLocaleString(),
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
                {
                  name: "URL",
                  value: decodeURIComponent(requestDetails.request_url),
                },
                { name: "Status", value: requestDetails.response_code },
                { name: "ID", value: requestDetails.id },
              ].map((item, index) => (
                <div className="swpl__modal--meta" key={index}>
                  <div className="swpl__modal--meta-name">{item.name}:</div>
                  <div className="swpl__modal--meta-value">{item.value}</div>
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
                    <div className="swpl__data">
                      {tab.name === "payload"
                        ? jsonView(requestDetails.request_payload)
                        : jsonView(requestDetails.request_headers)}
                    </div>
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
                    <div className="swpl__data">
                      {tab.name === "data"
                        ? jsonView(requestDetails.response_data)
                        : jsonView(requestDetails.response_headers)}
                    </div>
                  </div>
                )}
              </TabPanel>
            </div>
          ) : (
            <p>No request details available</p>
          )}
        </div>

        <div className="swpl__modal--footer">
          <button className="button" onClick={onClose}>
            Close
          </button>
        </div>
      </div>
    </div>
  );
};

export default RequestDetailsModal;
