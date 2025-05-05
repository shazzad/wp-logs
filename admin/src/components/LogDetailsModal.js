// src/components/LogDetailsModal.js
import React, { useState, useEffect } from "react";
import apiFetch from "@wordpress/api-fetch";
import { Spinner } from "@wordpress/components";
import ReactJsonView from "@microlink/react-json-view";

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
        path: `/swpl/v1/logs/${id}`,
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
      JSON.stringify(data, null, 2);
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
          <h2>Log Details</h2>
          <button className="swpl__modal--close-btn" onClick={onClose}>
            &times;
          </button>
        </div>

        <div className="swpl__modal--body">
          {isLoading ? (
            <div className="swpl__loading">
              <Spinner />
              <p>Loading log details...</p>
            </div>
          ) : error ? (
            <div className="notice notice-error">
              <p>{error}</p>
            </div>
          ) : logDetails ? (
            <div className="swpl__modal--content-rows">
              {[
                {
                  name: "Date",
                  value: new Date(logDetails.date_created).toLocaleString(),
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
                <div className="swpl__modal--meta" key={index}>
                  <div className="swpl__modal--meta-name">{item.name}:</div>
                  <div className="swpl__modal--meta-value">{item.value}</div>
                </div>
              ))}

              <div className="swpl-log-detail-item">
                <strong>Data:</strong>
                <data className="swpl__data">
                  {jsonView(logDetails.context)}
                </data>
              </div>
            </div>
          ) : (
            <p>No log details available</p>
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

export default LogDetailsModal;
