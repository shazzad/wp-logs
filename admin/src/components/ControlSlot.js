import React from "react";

const ControlSlot = ({ label, children, ...props }) => {
  return (
    <div className="hr__admin__setting">
      <div className="hr__admin__setting__header">
        {label && <div className="hr__admin__setting__label">{label}</div>}
        {props.help && (
          <div
            className="hr__admin__setting__meta hr__admin__setting__meta--help"
            dangerouslySetInnerHTML={{ __html: props.help }}
          ></div>
        )}
      </div>
      <div className="hr__admin__setting__control">
        {children}
        {props.loadingText && props.loading && (
          <div className="hr__admin__setting__meta hr__admin__setting__meta--loading">
            {props.loadingText}
          </div>
        )}
        {props.desc && (
          <div
            className="hr__admin__setting__meta hr__admin__setting__meta--desc"
            dangerouslySetInnerHTML={{ __html: props.desc }}
          ></div>
        )}
        {props.error && (
          <div
            className="hr__admin__setting__meta hr__admin__setting__meta--error"
            dangerouslySetInnerHTML={{ __html: props.error }}
          ></div>
        )}
      </div>
    </div>
  );
};

export default ControlSlot;
