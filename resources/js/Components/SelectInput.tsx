interface SelectInputProps extends React.ComponentPropsWithoutRef<'select'> {
    children: React.ReactNode
}

const SelectInput: React.FC<SelectInputProps> = ({ className, children, ...props }) => {
    return (
        <select {...props} className={'rounded-md border-slate-300 focus:border-slate-400 cursor-pointer !outline-none text-sm duration-150 ' + className}>
            {children}
        </select>
    )
}

export default SelectInput
