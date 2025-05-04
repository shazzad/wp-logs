import ControlSlot from "./ControlSlot";

const CustomTextareaControl = ({
  label,
  name,
  value,
  setSetting,
  help,
  ...props
}) => {
  if (props.onChange) {
    return (
      <ControlSlot label={label} help={help} {...props}>
        <div className="custom-textarea-control">
          <textarea
            id={props.id || "custom-textarea"}
            value={value}
            onChange={props.onChange}
            {...props}
          />
        </div>
      </ControlSlot>
    );
  }

  return (
    <ControlSlot label={label} help={help} {...props}>
      <div className="custom-textarea-control">
        <textarea
          id={props.id || "custom-textarea"}
          value={value}
          onChange={(e) => setSetting(name, e.target.value)}
          {...props}
        />
      </div>
    </ControlSlot>
  );
};

export default CustomTextareaControl;
