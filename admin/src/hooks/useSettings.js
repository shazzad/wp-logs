import { useEffect, useState } from "react";
import { toast } from "react-toastify";

const useSettings = (SETTING_ID, defaults = {}) => {
  const [oldSettings, setOldSettings] = useState({ ...defaults });
  const [settings, setSettings] = useState({ ...defaults });
  const [isFetching, setIsFetching] = useState(true);
  const [error, setError] = useState(null);
  const [hasChanged, setHasChanged] = useState(false);
  const [isSaving, setIsSaving] = useState(false);

  useEffect(() => {
    const fetchSettings = async () => {
      const nonce = window.swplAdminAppSettings?.nonce;
      const headers = {
        method: "GET",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": nonce,
        },
      };

      try {
        const endpoint = `/wp-json/swpl/v1/settings/${SETTING_ID}`;
        const response = await fetch(endpoint, headers);

        if (!response.ok) {
          throw new Error(`Error fetching settings: ${response.status}`);
        }

        const data = await response.json();
        setOldSettings(data);
        setSettings(data);
        setError(null);
      } catch (error) {
        setError(error.message);
      } finally {
        setIsFetching(false);
      }
    };
    fetchSettings();
  }, []);

  const setSetting = (key, value) => {
    setSettings((prev) => {
      return { ...prev, [key]: value };
    });

    if (oldSettings && oldSettings !== settings) {
      setHasChanged(true);
    }
  };

  const saveSettings = async () => {
    if (!SETTING_ID) {
      console.error("No SETTING_ID provided for saving settings.");
      return;
    }

    setIsSaving(true);
    const nonce = window.swplAdminAppSettings?.nonce;

    try {
      const response = await fetch(`/wp-json/swpl/v1/settings/${SETTING_ID}`, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": nonce,
        },
        body: JSON.stringify(settings),
      });

      if (!response.ok) {
        throw new Error("Failed to save settings");
      }

      toast.success("Settings updated!", {
        position: "bottom-right",
        autoClose: 3000,
      });
    } catch (error) {
      toast.error(`Error: ${error.message}`, {
        position: "bottom-right",
        autoClose: 3000,
      });
    } finally {
      setIsSaving(false);
      setHasChanged(false);
    }
  };

  return {
    settings,
    error,
    hasChanged,
    isFetching,
    isSaving,
    setSetting,
    saveSettings,
  };
};

export default useSettings;
