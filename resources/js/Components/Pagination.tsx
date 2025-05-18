import React from 'react';
import { Link } from '@inertiajs/react';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginationProps {
    links: PaginationLink[];
}

export function Pagination({ links }: PaginationProps) {
    // Αν δεν υπάρχουν πολλαπλές σελίδες, μην εμφανίζεις pagination
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap justify-center mt-4">
            {links.map((link, key) => {
                // Μετατροπή του HTML από το label (π.χ. "&laquo; Previous") σε καθαρό κείμενο
                const label = link.label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»');

                if (link.url === null) {
                    return (
                        <span
                            key={key}
                            className="mr-1 mb-1 px-4 py-2 text-sm border rounded text-gray-400"
                            dangerouslySetInnerHTML={{ __html: label }}
                        />
                    );
                }

                return (
                    <Link
                        key={key}
                        href={link.url}
                        className={`mr-1 mb-1 px-4 py-2 text-sm border rounded focus:outline-none ${link.active
                            ? 'border-typo-300 bg-typo-300 text-white'
                            : 'border-gray-300 bg-white text-typo-300 hover:bg-gray-50'
                            }`}
                    >
                        <span dangerouslySetInnerHTML={{ __html: label }} />
                    </Link>
                );
            })}
        </div>
    );
} 