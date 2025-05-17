import ApplicationLogo from "@/Components/ApplicationLogo";
import { Link } from "@inertiajs/react";
import { PropsWithChildren } from "react";

export default function Guest({ children }: PropsWithChildren) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-slate-200 pt-6 sm:justify-center sm:pt-0">
            <div className="flex justify-center items-center mb-5">
                <Link href="/">
                    <ApplicationLogo />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 max-w-[50vw] sm:rounded-lg">
                {children}
            </div>
        </div>
    );
}
