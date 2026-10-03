import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { PageProps } from '@/types';
import { Head } from '@inertiajs/react';
import UpdatePasswordForm from './Partials/UpdatePasswordForm';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm';

export default function Edit({
    mustVerifyEmail,
    status,
}: PageProps<{ mustVerifyEmail: boolean; status?: string }>) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <p className="text-sm text-slate-500">Akun</p>
                    <h1 className="text-[26px] font-bold text-slate-900">Profil Saya</h1>
                </div>
            }
        >
            <Head title="Profil" />

            <div className="mx-auto max-w-3xl space-y-6 p-4 sm:p-6 lg:p-8">
                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <UpdateProfileInformationForm
                        mustVerifyEmail={mustVerifyEmail}
                        status={status}
                    />
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    <UpdatePasswordForm />
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
