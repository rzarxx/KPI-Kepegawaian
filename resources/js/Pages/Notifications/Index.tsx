import { Button } from '@/Components/ui/button';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { formatDistanceToNow } from 'date-fns';
import { id } from 'date-fns/locale';
import { Bell, BellRing, CheckCheck, ExternalLink } from 'lucide-react';
import { Head, Link } from '@inertiajs/react';

type Notification = {
    id: string;
    title: string;
    message: string;
    url?: string;
    level: 'info' | 'success' | 'warning' | 'error';
    read_at?: string;
    created_at: string;
};
type Paginated<T> = { data: T[]; links: { url?: string; label: string; active: boolean }[] };

export default function Index({ notifications }: { notifications: Paginated<Notification> }) {
    const unread = notifications.data.some((notification) => !notification.read_at);
    return (
        <AuthenticatedLayout header={<div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center"><div><p className="text-sm text-slate-500">Pemberitahuan</p><h1 className="text-[26px] font-bold text-slate-900">Notifikasi</h1></div>{unread && <Button asChild variant="secondary"><Link as="button" method="post" href={route('notifications.read-all')}><CheckCheck size={17} />Tandai semua dibaca</Link></Button>}</div>}>
            <Head title="Notifikasi" />
            <div className="mx-auto max-w-4xl p-4 sm:p-6 lg:p-8">
                <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    {notifications.data.length ? <div className="divide-y divide-slate-100">{notifications.data.map((notification) => <article className={'flex gap-4 p-5 ' + (!notification.read_at ? 'bg-green-50/40' : '')} key={notification.id}><span className={'mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-full ' + levelClasses[notification.level]}>{notification.read_at ? <Bell size={18} /> : <BellRing size={18} />}</span><div className="min-w-0 flex-1"><div className="flex flex-col justify-between gap-1 sm:flex-row"><p className="font-semibold text-slate-900">{notification.title}</p><span className="whitespace-nowrap text-xs text-slate-400">{formatDistanceToNow(new Date(notification.created_at), { addSuffix: true, locale: id })}</span></div><p className="mt-1 text-sm leading-6 text-slate-600">{notification.message}</p><div className="mt-3 flex flex-wrap gap-3">{notification.url && <Link className="inline-flex items-center gap-1 text-sm font-semibold text-green-700 hover:text-green-800" href={notification.url}><ExternalLink size={15} />Buka detail</Link>}{!notification.read_at && <Link as="button" method="post" preserveScroll className="text-sm font-medium text-slate-500 hover:text-slate-800" href={route('notifications.read', notification.id)}>Tandai dibaca</Link>}</div></div></article>)}</div> : <div className="py-16 text-center"><Bell className="mx-auto text-slate-300" size={32} /><p className="mt-3 font-semibold text-slate-800">Belum ada notifikasi</p><p className="mt-1 text-sm text-slate-500">Pembaruan penting akan muncul di sini.</p></div>}
                </section>
                {notifications.links.length > 3 && <nav className="mt-5 flex flex-wrap justify-center gap-1" aria-label="Navigasi halaman">{notifications.links.map((link, index) => link.url ? <Link className={'rounded-lg border px-3 py-2 text-sm ' + (link.active ? 'border-green-600 bg-green-50 text-green-800' : 'border-slate-200 bg-white text-slate-600')} href={link.url} key={index}>{paginationLabel(link.label)}</Link> : <span className="rounded-lg border border-slate-100 px-3 py-2 text-sm text-slate-300" key={index}>{paginationLabel(link.label)}</span>)}</nav>}
            </div>
        </AuthenticatedLayout>
    );
}

const levelClasses = {
    info: 'bg-blue-50 text-blue-700',
    success: 'bg-green-50 text-green-700',
    warning: 'bg-amber-50 text-amber-700',
    error: 'bg-red-50 text-red-700',
};
function paginationLabel(label: string) { return label.replace('&laquo;', '‹').replace('&raquo;', '›'); }
