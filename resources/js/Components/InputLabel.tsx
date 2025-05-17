import { LabelHTMLAttributes } from 'react';

export default function InputLabel({
    value,
    className = '',
    children,
    ...props
}: LabelHTMLAttributes<HTMLLabelElement> & { value?: string }) {
    return (
        <label
            {...props}
            className={
                "block text-sm font-medium cursor-pointer " +
                className
            }
        >
            {value ? value : children}
        </label>
    );
}
