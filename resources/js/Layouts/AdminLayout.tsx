import React, { ReactNode } from 'react';
import { Link, usePage } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import { BriefcaseBusiness, LayoutDashboard, LogOut, User } from 'lucide-react';

interface AdminLayoutProps {
    children: ReactNode;
}

export default function AdminLayout({ children }: AdminLayoutProps) {
    const { auth } = usePage().props as any;

    return (
        <div className="min-h-screen bg-gray-100 flex">
            <nav className="bg-white flex flex-col">
                <div className="flex flex-col flex-1">
                    <Link href={route('admin.dashboard')} className="p-4">
                        <ApplicationLogo />
                    </Link>
                    <div className="flex flex-col flex-1">
                        <Link
                            href={route('admin.dashboard')}
                            className={`inline-flex items-center gap-2 py-3 text-sm font-medium px-4 duration-300 hover:bg-slate-200 ${route().current('admin.dashboard') ? 'bg-slate-200' : ''}`}
                        >
                            <LayoutDashboard size={16} strokeWidth={1.5} />
                            Γενικά
                        </Link>
                        <Link
                            href={route('admin.tenants.index')}
                            className={`inline-flex items-center gap-2 py-3 text-sm font-medium px-4 duration-300 hover:bg-slate-200 ${route().current('admin.tenants.index') ? 'bg-slate-200' : ''}`}
                        >
                            <BriefcaseBusiness size={16} strokeWidth={1.5} />
                            Επιχειρήσεις
                        </Link>
                    </div>
                    <div className="flex flex-col border-t border-slate-200">
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="inline-flex items-center gap-2 py-3 text-sm font-medium px-4 duration-300 hover:bg-slate-200"
                        >
                            <User size={16} strokeWidth={1.5} />
                            {auth.user.name}
                        </Link>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="inline-flex items-center gap-2 py-3 text-sm font-medium px-4 bg-primary-400 hover:bg-primary-500 duration-300 text-white"
                        >
                            <LogOut size={16} strokeWidth={1.5} />
                            Αποσύνδεση
                        </Link>
                    </div>
                </div>

                {/* <div className="hidden sm:flex sm:items-center sm:ml-6">
                    <div className="ml-3 relative">
                        <div className="flex items-center">
                            <span className="text-gray-700 text-sm mr-4">{auth.user.name}</span>

                        </div>
                    </div>
                </div> */}
            </nav>
            <main className="flex-1 bg-slate-200">{children}</main>
        </div>
    );
} 