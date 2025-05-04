import ControlSlot from "./ControlSlot";

const CustomToggleControl = ({ name, value, ...props }) => {
  const checked = value === "yes" || value === true;
  if (!props.onChange) {
    if (props.setSetting) {
      props.onChange = (e) =>
        props.setSetting(name, e.target.checked ? "yes" : "no");
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
      <div className="hr__admin__toggle-switch">
        <input
          id={name}
          type="checkbox"
          className="hr__admin__toggle-switch__input"
          checked={checked}
          onChange={props.onChange}
          disabled={props.disabled}
        />
        <label
          htmlFor={name}
          className="hr__admin__toggle-switch__label"
        ></label>
      </div>
    </ControlSlot>
  );
};

export default CustomToggleControl;
