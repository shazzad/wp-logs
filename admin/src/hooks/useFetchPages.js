import { useEffect, useState } from "react";

const useFetchPages = () => {
  const [pages, setPages] = useState(null);
  const [isFetching, setIsFetching] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    const fetchSettings = async () => {
      const nonce = window.homelocalApiSettings?.nonce;
      const headers = {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": nonce,
        },
      };

      try {
        const endpoint = `/wp-json/wp/v2/pages?_fields=id,title&per_page=100`;
        const response = await fetch(endpoint, headers);

        if (!response.ok) {
          throw new Error(`Error fetching settings: ${response.status}`);
        }

        const data = await response.json();
        setPages(data);
        setError(null);
      } catch (error) {
        setError(error.message);
      } finally {
        setIsFetching(false);
      }
    };
    fetchSettings();
  }, []);

  return {
    pages,
    error,
    isFetching,
  };
};

export default useFetchPages;
