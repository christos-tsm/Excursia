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
                    "rounded-md border-slate-300 focus:border-slate-400 duration-300 text-sm read-only:!border-slate-300 read-only:opacity-65 " +
                    className
                }
                ref={ref || localRef}
            />
        );
    },
); 