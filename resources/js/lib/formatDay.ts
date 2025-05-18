export const formatDay = (date: string, format: 'd/m/Y' | 'd/m/Y H:i') => {
    const dateObj = new Date(date);
    const day = dateObj.getDate();
    const month = dateObj.getMonth() + 1;
    const year = dateObj.getFullYear();
    const hour = dateObj.getHours();
    const minute = dateObj.getMinutes();
    if (format === 'd/m/Y H:i') {
        return `${day}/${month}/${year} - ${hour.toString().padStart(2, '0')}:${minute.toString().padStart(2, '0')}`;
    }
    return `${day}/${month}/${year}`;
}

