import { forwardRef, useEffect, useRef, TextareaHTMLAttributes } from 'react';

export default forwardRef<HTMLTextAreaElement, TextareaHTMLAttributes<HTMLTextAreaElement> & { isFocused?: boolean }>(
    function TextArea({ className = '', isFocused = false, ...props }, ref) {
        const localRef = useRef<HTMLTextAreaElement>(null);

        useEffect(() => {
            if (isFocused) {
                localRef.current?.focus();
            }
        }, [isFocused]);

        return (
            <textarea
                {...props}
                className={
                    'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm ' +
                    className
                }
                ref={ref || localRef}
            />
        );
    },
); 