// src/Logs.js
import React, { useState, useEffect } from "react";
import apiFetch from "@wordpress/api-fetch";
import SimplePagination from "../components/SimplePagination";
import RequestDetailsModal from "../components/RequestDetailsModal";
import RequestFilters from "../components/RequestFilters";
import RequestTable from "../components/RequestTable";
import BulkActions from "../components/BulkActions";
import LogsDeleteConfirmationModal from "../components/LogsDeleteConfirmationModal";

const Requests = () => {
  const [requests, setRequests] = useState([]);
  const [displayLogs, setDisplayLogs] = useState([]); // New state for displayed requests
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [selectedLogId, setSelectedLogId] = useState(null);
  const [methodFilter, setLevelFilter] = useState("");
  const [hostnameFilter, setSourceFilter] = useState("");
  const [sortField, setSortField] = useState("id");
  const [sortOrder, setSortOrder] = useState("desc");
  const [selectedLogs, setSelectedLogs] = useState([]);
  const [isDeleting, setIsDeleting] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(null); // null, 'selected', or 'all'
  const [perPage, setPerPage] = useState(10); // Default to 10 items per page

  // Get available levels and sources from the global settings
  const availableMethods = window.swplAdminAppSettings?.methods || {};
  const availableHostnames = window.swplAdminAppSettings?.hostnames || {};

  // Prepare level options for the select dropdown
  const methodOptions = [
    { label: "All Methods", value: "" },
    // Convert object to array of options if needed
    ...(Array.isArray(availableMethods)
      ? availableMethods.map((level) => ({
          label: level.charAt(0).toUpperCase() + level.slice(1),
          value: level,
        }))
      : Object.keys(availableMethods).map((level) => ({
          label: level.charAt(0).toUpperCase() + level.slice(1),
          value: level,
        }))),
  ];

  // Prepare source options for the select dropdown
  const hostnameOptions = [
    { label: "All Hosts", value: "" },
    // Convert object to array of options if needed
    ...(Array.isArray(availableHostnames)
      ? availableHostnames.map((source) => ({
          label: source,
          value: source,
        }))
      : Object.keys(availableHostnames).map((source) => ({
          label: source,
          value: source,
        }))),
  ];

  // Per page options
  const perPageOptions = [
    { label: "10 per page", value: 10 },
    { label: "25 per page", value: 25 },
    { label: "50 per page", value: 50 },
    { label: "100 per page", value: 100 },
  ];

  useEffect(() => {
    // Set up API authentication with the provided credentials
    if (swplAdminAppSettings) {
      apiFetch.use(apiFetch.createNonceMiddleware(swplAdminAppSettings.nonce));
      apiFetch.use(apiFetch.createRootURLMiddleware(swplAdminAppSettings.root));
    }

    // Fetch requests when component mounts, page changes, or filters change
    fetchLogs();
  }, [
    currentPage,
    searchTerm,
    methodFilter,
    hostnameFilter,
    sortField,
    sortOrder,
    perPage,
  ]);

  // Clear selected requests when page changes or filters change
  useEffect(() => {
    setSelectedLogs([]);
  }, [currentPage, searchTerm, methodFilter, hostnameFilter]);

  // Update displayLogs when requests state is updated
  useEffect(() => {
    setDisplayLogs(requests);
  }, [requests]);

  const fetchLogs = async () => {
    try {
      setIsLoading(true);
      // We don't clear the requests here, which keeps the existing content visible

      // Build query parameters for pagination, search, filters, and sorting
      let queryParams = `?page=${currentPage}&per_page=${perPage}`;

      if (searchTerm.trim()) {
        queryParams += `&search=${encodeURIComponent(searchTerm)}`;
      }

      if (methodFilter) {
        queryParams += `&request_method=${encodeURIComponent(methodFilter)}`;
      }

      if (hostnameFilter) {
        queryParams += `&request_hostname=${encodeURIComponent(
          hostnameFilter
        )}`;
      }

      // Add sorting parameters
      queryParams += `&orderby=${encodeURIComponent(
        sortField
      )}&order=${encodeURIComponent(sortOrder)}`;

      // Add fields parameter to limit the response fields.
      queryParams += `&fields=id,request_method,request_url,response_code,response_size,date_created`;

      const response = await apiFetch({
        path: `/swpl/v1/requests${queryParams}`,
        // Parse headers to get pagination information
        parse: false,
      });

      // Get response data
      const data = await response.json();

      // Get total pages and total items from headers
      const totalItems = parseInt(response.headers.get("X-WP-Total"), 10) || 0;
      const totalPages =
        parseInt(response.headers.get("X-WP-TotalPages"), 10) || 1;

      // Check if the response has data in the expected format
      if (data && data.data && Array.isArray(data.data)) {
        setRequests(data.data);
        setTotalItems(totalItems);
        setTotalPages(totalPages);
      } else {
        setRequests([]);
        setError("Unexpected response format from API");
      }
    } catch (err) {
      console.error("Error fetching requests:", err);
      setError("Failed to fetch requests. Please try again later.");
    } finally {
      setIsLoading(false);
    }
  };

  const handleSearch = (value) => {
    setSearchTerm(value);
    setCurrentPage(1); // Reset to first page when search changes
  };

  const handleLevelChange = (value) => {
    setLevelFilter(value);
    setCurrentPage(1); // Reset to first page when filter changes
  };

  const handleSourceChange = (value) => {
    setSourceFilter(value);
    setCurrentPage(1); // Reset to first page when filter changes
  };

  const handlePerPageChange = (value) => {
    setPerPage(parseInt(value));
    setCurrentPage(1); // Reset to first page when per page changes
  };

  const handlePageChange = (page) => {
    if (page >= 1 && page <= totalPages) {
      setCurrentPage(page);
    }
  };

  const handleSort = (field) => {
    // If clicking the same field that is already being sorted,
    // toggle the sort order. Otherwise, set the new field and default to asc.
    if (field === sortField) {
      setSortOrder(sortOrder === "asc" ? "desc" : "asc");
    } else {
      setSortField(field);
      setSortOrder("asc");
    }
    setCurrentPage(1); // Reset to first page when sort changes
  };

  const resetFilters = () => {
    setSearchTerm("");
    setLevelFilter("");
    setSourceFilter("");
    setSortField("id");
    setSortOrder("desc");
    setPerPage(10); // Reset to default per page
    setCurrentPage(1);
  };

  const openLogDetails = (logId) => {
    setSelectedLogId(logId);
  };

  const closeLogDetails = () => {
    setSelectedLogId(null);
  };

  // Handle log selection
  const toggleLogSelection = (logId) => {
    setSelectedLogs((prevSelected) => {
      if (prevSelected.includes(logId)) {
        return prevSelected.filter((id) => id !== logId);
      } else {
        return [...prevSelected, logId];
      }
    });
  };

  // Handle select all requests on current page
  const toggleSelectAll = () => {
    if (selectedLogs.length === displayLogs.length) {
      // Deselect all if all are selected
      setSelectedLogs([]);
    } else {
      // Select all requests on the current page
      setSelectedLogs(displayLogs.map((log) => log.id));
    }
  };

  // Show delete confirmation modal
  const showDeleteConfirmation = (type) => {
    setConfirmDelete(type);
  };

  // Cancel delete
  const cancelDelete = () => {
    setConfirmDelete(null);
  };

  // Execute deletion
  const executeDelete = async () => {
    try {
      setIsDeleting(true);

      // Determine whether to delete selected or all requests
      if (confirmDelete === "all") {
        // Delete all requests
        await apiFetch({
          path: "/swpl/v1/requests",
          method: "DELETE",
        });
      } else if (confirmDelete === "selected" && selectedLogs.length > 0) {
        // Delete selected requests
        await apiFetch({
          path: "/swpl/v1/requests",
          method: "DELETE",
          data: { ids: selectedLogs },
        });
      }

      // Close confirmation modal
      setConfirmDelete(null);

      // Refresh the requests list
      fetchLogs();

      // Clear selected requests
      setSelectedLogs([]);
    } catch (err) {
      console.error("Error deleting requests:", err);
      setError("Failed to delete requests. Please try again later.");
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <div className="swpl__page__content">
      <h2>Requests</h2>

      {error && (
        <div className="notice notice-error">
          <p>{error}</p>
        </div>
      )}

      <div className="swpl__admin__filters">
        <RequestFilters
          searchTerm={searchTerm}
          methodFilter={methodFilter}
          hostnameFilter={hostnameFilter}
          methodOptions={methodOptions}
          hostnameOptions={hostnameOptions}
          isLoading={isLoading}
          onSearchChange={handleSearch}
          onMethodChange={handleLevelChange}
          onSourceChange={handleSourceChange}
          onApplyFilters={fetchLogs}
          onResetFilters={resetFilters}
          perPage={perPage}
          perPageOptions={perPageOptions}
          onPerPageChange={handlePerPageChange}
        />

        <BulkActions
          selectedCount={selectedLogs.length}
          isLoading={isLoading}
          isDeleting={isDeleting}
          hasLogs={requests.length > 0}
          onDeleteSelected={() => showDeleteConfirmation("selected")}
          onDeleteAll={() => showDeleteConfirmation("all")}
        />
      </div>

      <RequestTable
        requests={requests}
        isLoading={isLoading}
        isLoadingNewData={isLoading && requests.length > 0}
        selectedLogs={selectedLogs}
        sortField={sortField}
        sortOrder={sortOrder}
        onToggleSelectAll={toggleSelectAll}
        onToggleLogSelection={toggleLogSelection}
        onSort={handleSort}
        onViewDetails={openLogDetails}
      />

      {totalPages > 1 && (
        <div className="swpl__pagination">
          <div className="swpl__pagination--info">
            Showing {requests.length} of {totalItems} requests
          </div>
          <SimplePagination
            currentPage={currentPage}
            totalPages={totalPages}
            onPageChange={handlePageChange}
          />
        </div>
      )}

      {selectedLogId && (
        <RequestDetailsModal
          requestId={selectedLogId}
          onClose={closeLogDetails}
        />
      )}

      {confirmDelete && (
        <LogsDeleteConfirmationModal
          type={confirmDelete}
          selectedCount={selectedLogs.length}
          isDeleting={isDeleting}
          onCancel={cancelDelete}
          onConfirm={executeDelete}
        />
      )}
    </div>
  );
};

export default Requests;
