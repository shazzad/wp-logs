import React from "react";
import { __ } from "@wordpress/i18n";
import { Button } from "@wordpress/components";
import CustomInputControl from "../components/CustomInputControl";
import CustomTextareaControl from "../components/CustomTextareaControl";

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
    <div className="swpl__page__content">
      <h2>{__("User Settings", "swpl")}</h2>

      <div className="swpl__admin__section">
        {settingsError && (
          <p>
            {__("Error loading settings:", "swpl")} {settingsError}
          </p>
        )}

        <CustomInputControl
          label={__("Retain Logs For", "swpl")}
          desc={__(
            "Set the maximum number of days to retain logs. Default is 0, infinite.",
            "swpl"
          )}
          value={settings?.swpl_log_retention_days || ""}
          name={"swpl_log_retention_days"}
          setSetting={setSetting}
        />

        <CustomInputControl
          label={__("Retain Requests for", "swpl")}
          desc={__(
            "Set the maximum number of days to retain requests. Default is 0, infinite.",
            "swpl"
          )}
          value={settings?.swpl_request_retention_days || ""}
          name={"swpl_request_retention_days"}
          setSetting={setSetting}
        />

        <CustomTextareaControl
          label={__("Request URLs to Log", "swpl")}
          desc={__(
            "Enter one url address per line. We will match it against the left part of the url.",
            "swpl"
          )}
          value={settings?.swpl_logged_request_urls || ""}
          name={"swpl_logged_request_urls"}
          setSetting={setSetting}
        />
      </div>

      <div className="swpl__admin__footer">
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
