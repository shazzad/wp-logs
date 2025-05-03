// src/Logs.js
import React, { useState, useEffect } from "react";
import apiFetch from "@wordpress/api-fetch";
import SimplePagination from "../components/SimplePagination";
import LogDetailsModal from "../components/LogDetailsModal";
import LogFilters from "../components/LogFilters";
import LogTable from "../components/LogTable";
import BulkActions from "../components/BulkActions";
import DeleteConfirmationModal from "../components/DeleteConfirmationModal";
import "../styles/logs.scss";

const Logs = () => {
  const [logs, setLogs] = useState([]);
  const [displayLogs, setDisplayLogs] = useState([]); // New state for displayed logs
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [totalItems, setTotalItems] = useState(0);
  const [selectedLogId, setSelectedLogId] = useState(null);
  const [levelFilter, setLevelFilter] = useState("");
  const [sourceFilter, setSourceFilter] = useState("");
  const [sortField, setSortField] = useState("id");
  const [sortOrder, setSortOrder] = useState("desc");
  const [selectedLogs, setSelectedLogs] = useState([]);
  const [isDeleting, setIsDeleting] = useState(false);
  const [confirmDelete, setConfirmDelete] = useState(null); // null, 'selected', or 'all'
  const perPage = 10;

  // Get available levels and sources from the global settings
  const availableLevels = window.swplAdminAppSettings?.levels || {};
  const availableSources = window.swplAdminAppSettings?.logSources || {};

  // Prepare level options for the select dropdown
  const levelOptions = [
    { label: "All Levels", value: "" },
    // Convert object to array of options if needed
    ...(Array.isArray(availableLevels)
      ? availableLevels.map((level) => ({
          label: level.charAt(0).toUpperCase() + level.slice(1),
          value: level,
        }))
      : Object.keys(availableLevels).map((level) => ({
          label: level.charAt(0).toUpperCase() + level.slice(1),
          value: level,
        }))),
  ];

  // Prepare source options for the select dropdown
  const sourceOptions = [
    { label: "All Sources", value: "" },
    // Convert object to array of options if needed
    ...(Array.isArray(availableSources)
      ? availableSources.map((source) => ({
          label: source,
          value: source,
        }))
      : Object.keys(availableSources).map((source) => ({
          label: source,
          value: source,
        }))),
  ];

  useEffect(() => {
    // Set up API authentication with the provided credentials
    if (swplAdminAppSettings) {
      apiFetch.use(apiFetch.createNonceMiddleware(swplAdminAppSettings.nonce));
      apiFetch.use(apiFetch.createRootURLMiddleware(swplAdminAppSettings.root));
    }

    // Fetch logs when component mounts, page changes, or filters change
    fetchLogs();
  }, [
    currentPage,
    searchTerm,
    levelFilter,
    sourceFilter,
    sortField,
    sortOrder,
  ]);

  // Clear selected logs when page changes or filters change
  useEffect(() => {
    setSelectedLogs([]);
  }, [currentPage, searchTerm, levelFilter, sourceFilter]);

  // Update displayLogs when logs state is updated
  useEffect(() => {
    setDisplayLogs(logs);
  }, [logs]);

  const fetchLogs = async () => {
    try {
      setIsLoading(true);
      // We don't clear the logs here, which keeps the existing content visible

      // Build query parameters for pagination, search, filters, and sorting
      let queryParams = `?page=${currentPage}&per_page=${perPage}`;

      if (searchTerm.trim()) {
        queryParams += `&search=${encodeURIComponent(searchTerm)}`;
      }

      if (levelFilter) {
        queryParams += `&level=${encodeURIComponent(levelFilter)}`;
      }

      if (sourceFilter) {
        queryParams += `&source=${encodeURIComponent(sourceFilter)}`;
      }

      // Add sorting parameters
      queryParams += `&orderby=${encodeURIComponent(
        sortField
      )}&order=${encodeURIComponent(sortOrder)}`;

      const response = await apiFetch({
        path: `/wp/v2/logs${queryParams}`,
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
        setLogs(data.data);
        setTotalItems(totalItems);
        setTotalPages(totalPages);
      } else {
        setLogs([]);
        setError("Unexpected response format from API");
      }
    } catch (err) {
      console.error("Error fetching logs:", err);
      setError("Failed to fetch logs. Please try again later.");
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

  // Handle select all logs on current page
  const toggleSelectAll = () => {
    if (selectedLogs.length === displayLogs.length) {
      // Deselect all if all are selected
      setSelectedLogs([]);
    } else {
      // Select all logs on the current page
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

      // Determine whether to delete selected or all logs
      if (confirmDelete === "all") {
        // Delete all logs
        await apiFetch({
          path: "/wp/v2/logs",
          method: "DELETE",
        });
      } else if (confirmDelete === "selected" && selectedLogs.length > 0) {
        // Delete selected logs
        await apiFetch({
          path: "/wp/v2/logs",
          method: "DELETE",
          data: { ids: selectedLogs },
        });
      }

      // Close confirmation modal
      setConfirmDelete(null);

      // Refresh the logs list
      fetchLogs();

      // Clear selected logs
      setSelectedLogs([]);
    } catch (err) {
      console.error("Error deleting logs:", err);
      setError("Failed to delete logs. Please try again later.");
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <div className="swpl-admin-page">
      <h1 className="wpl-admin-page-heading">Logs</h1>

      <LogFilters
        searchTerm={searchTerm}
        levelFilter={levelFilter}
        sourceFilter={sourceFilter}
        levelOptions={levelOptions}
        sourceOptions={sourceOptions}
        isLoading={isLoading}
        onSearchChange={handleSearch}
        onLevelChange={handleLevelChange}
        onSourceChange={handleSourceChange}
        onApplyFilters={fetchLogs}
        onResetFilters={resetFilters}
      />

      {error && (
        <div className="notice notice-error">
          <p>{error}</p>
        </div>
      )}

      <BulkActions
        selectedCount={selectedLogs.length}
        isLoading={isLoading}
        isDeleting={isDeleting}
        hasLogs={displayLogs.length > 0}
        onDeleteSelected={() => showDeleteConfirmation("selected")}
        onDeleteAll={() => showDeleteConfirmation("all")}
      />

      <LogTable
        logs={displayLogs}
        isLoading={isLoading}
        isLoadingNewData={isLoading && displayLogs.length > 0}
        selectedLogs={selectedLogs}
        sortField={sortField}
        sortOrder={sortOrder}
        onToggleSelectAll={toggleSelectAll}
        onToggleLogSelection={toggleLogSelection}
        onSort={handleSort}
        onViewDetails={openLogDetails}
      />

      {totalPages > 1 && (
        <div className="swpl-pagination">
          <div className="swpl-pagination-info">
            Showing {displayLogs.length} of {totalItems} logs
          </div>
          <SimplePagination
            currentPage={currentPage}
            totalPages={totalPages}
            onPageChange={handlePageChange}
          />
        </div>
      )}

      {selectedLogId && (
        <LogDetailsModal logId={selectedLogId} onClose={closeLogDetails} />
      )}

      {confirmDelete && (
        <DeleteConfirmationModal
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

export default Logs;
