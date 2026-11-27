/**
 * WordPress dependencies
 */
import { __ } from "@wordpress/i18n";
import { RangeControl, SelectControl } from "@wordpress/components";

const HoverTransitionControls = ({ attributes, setAttributes }) => {
	const {
		hoverTransitionDuration,
		hoverTransitionTiming,
		hoverTextColor,
		hoverBackgroundColor,
		hoverBorderColor,
		customHoverTextColor,
		customHoverBackgroundColor,
		customHoverBorderColor,
	} = attributes;

	const timingOptions = [
		{ label: __("Ease", "blocklayouts-hover-color-controls"), value: "ease" },
		{ label: __("Linear", "blocklayouts-hover-color-controls"), value: "linear" },
		{ label: __("Ease-in", "blocklayouts-hover-color-controls"), value: "ease-in" },
		{ label: __("Ease-out", "blocklayouts-hover-color-controls"), value: "ease-out" },
		{ label: __("Ease-in-out", "blocklayouts-hover-color-controls"), value: "ease-in-out" },
	];

	return (
		<>
			{(customHoverBorderColor ||
				customHoverTextColor ||
				customHoverBackgroundColor ||
				hoverTextColor ||
				hoverBackgroundColor ||
				hoverBorderColor) && (
				<div
					className="blocklayouts-hover-color-controls__transition-controls"
					style={{
						gridTemplateColumns: "repeat(2, minmax(0px, 1fr))",
						gap: "calc(16px)",
						gridColumn: "1 / -1",
					}}
				>
					<RangeControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__("Transition Duration", "blocklayouts-hover-color-controls")}
						value={hoverTransitionDuration}
						onChange={(value) =>
							setAttributes({ hoverTransitionDuration: value })
						}
						min={0}
						max={2000}
						step={50}
						initialPosition={200}
						help={__("Duration in milliseconds", "blocklayouts-hover-color-controls")}
					/>

					<SelectControl
						__next40pxDefaultSize
						__nextHasNoMarginBottom
						label={__("Timing Function", "blocklayouts-hover-color-controls")}
						value={hoverTransitionTiming}
						options={timingOptions}
						onChange={(value) =>
							setAttributes({ hoverTransitionTiming: value })
						}
					/>
				</div>
			)}
		</>
	);
};

export default HoverTransitionControls;
