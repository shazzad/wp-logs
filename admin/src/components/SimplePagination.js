// src/components/SimplePagination.js
import React from "react";

const SimplePagination = ({ currentPage, totalPages, onPageChange }) => {
  const pageNumbers = [];

  // Calculate which page numbers to show (show max 5 pages)
  let startPage = Math.max(1, currentPage - 2);
  let endPage = Math.min(totalPages, startPage + 4);

  // Adjust start if we're near the end
  if (endPage - startPage < 4 && startPage > 1) {
    startPage = Math.max(1, endPage - 4);
  }

  for (let i = startPage; i <= endPage; i++) {
    pageNumbers.push(i);
  }

  return (
    <div className="swpl-pagination-nav">
      <button
        onClick={() => onPageChange(currentPage - 1)}
        disabled={currentPage === 1}
        className="button"
      >
        &laquo; Previous
      </button>

      {startPage > 1 && (
        <>
          <button onClick={() => onPageChange(1)} className="button">
            1
          </button>
          {startPage > 2 && (
            <span className="swpl-pagination-ellipsis">...</span>
          )}
        </>
      )}

      {pageNumbers.map((number) => (
        <button
          key={number}
          onClick={() => onPageChange(number)}
          className={`button ${currentPage === number ? "button-primary" : ""}`}
        >
          {number}
        </button>
      ))}

      {endPage < totalPages && (
        <>
          {endPage < totalPages - 1 && (
            <span className="swpl-pagination-ellipsis">...</span>
          )}
          <button onClick={() => onPageChange(totalPages)} className="button">
            {totalPages}
          </button>
        </>
      )}

      <button
        onClick={() => onPageChange(currentPage + 1)}
        disabled={currentPage === totalPages}
        className="button"
      >
        Next &raquo;
      </button>
    </div>
  );
};

export default SimplePagination;
