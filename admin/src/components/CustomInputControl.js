import React from "react";
import ControlSlot from "./ControlSlot";

const CustomInputControl = ({ name, value, type = "text", ...props }) => {
  if (!props.onChange) {
    if (props.setSetting) {
      props.onChange = (e) => props.setSetting(name, e.target.value);
    } else {
      console.error(
        "CustomInputControl: setSetting prop is required when onChange is not provided",
      );
    }
  }

  if (!props.disabled) {
    props.disabled = false;
  }

  return (
    <ControlSlot {...props}>
      <input
        type={type}
        value={value}
        disabled={props.disabled}
        onChange={props.onChange}
      />
    </ControlSlot>
  );
};

export default CustomInputControl;
