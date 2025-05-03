import React from "react";
import { __ } from "@wordpress/i18n";
import { Button } from "@wordpress/components";
import CustomInputControl from "../components/CustomInputControl";

import useSettings from "../hooks/useSettings";

const SETTING_ID = "plugin_settings";

const PluginSettings = () => {
  const {
    settings,
    isFetching,
    error: settingsError,
    hasChanged,
    setSetting,
    isSaving,
    saveSettings,
  } = useSettings(SETTING_ID);

  return (
    <div className="hr__admin__page">
      <h2 className="hr__admin__title">{__("User Settings", "swpl")}</h2>

      <div className="hr__admin__section">
        {settingsError && (
          <p>
            {__("Error loading settings:", "swpl")} {settingsError}
          </p>
        )}

        <CustomInputControl
          label={__("Max logs threshold", "swpl")}
          desc={__(
            "Set the maximum number of logs to be stored in the database. Default is 1000.",
            "swpl"
          )}
          value={settings?.swpl_max_logs_threshold || ""}
          name={"swpl_max_logs_threshold"}
          setSetting={setSetting}
        />

        <CustomInputControl
          label={__("Max requests threshold", "swpl")}
          desc={__(
            "Set the maximum number of requests to be stored in the database. Default is 1000.",
            "swpl"
          )}
          value={settings?.swpl_max_requests_threshold || ""}
          name={"swpl_max_requests_threshold"}
          setSetting={setSetting}
        />
      </div>

      <div className="hr__admin__footer">
        <Button
          __next40pxDefaultSize
          variant="primary"
          onClick={saveSettings}
          disabled={isFetching || isSaving}
          isBusy={isSaving}
        >
          {isSaving ? "Saving..." : "Save Settings"}
        </Button>
      </div>
    </div>
  );
};

export default PluginSettings;
