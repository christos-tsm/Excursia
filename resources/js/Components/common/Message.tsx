import React from 'react'

const Message = ({ message, type, className }: { message: string, type: 'success' | 'error', className?: string }) => {
    return (
        <div className={`px-4 py-2 border text-sm rounded text-center ${type === 'success' ? 'bg-green-100 border-green-400 text-green-700' : 'bg-red-100 border-red-400 text-red-700'} ${className}`}>
            {message}
        </div>
    )
}

export default Message