/* eslint-disable react/prop-types */
import { TextControl } from "@wordpress/components";
import { useBlockProps } from "@wordpress/block-editor";
import "./editor.scss";

export function Editor({ value, onChange, isSelected }) {
  return isSelected ? (
    <TextControl value={value} onChange={onChange} />
  ) : (
    <p>{value}</p>
  );
}

export default function Edit({ attributes, setAttributes, isSelected }) {
  return (
    // eslint-disable-next-line react/jsx-props-no-spreading
    <div {...useBlockProps()}>
      <Editor
        isSelected={isSelected}
        value={attributes.content}
        onChange={(content) => setAttributes({ content })}
      />
    </div>
  );
}
