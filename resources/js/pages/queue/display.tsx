import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { cn } from '@/lib/utils';

type QueueEntry = {
    id: number;
    code: string;
    position: number;
    status: 'pending' | 'calling' | 'called' | 'skipped';
};

type Snapshot = {
    current: string | null;
    entries: QueueEntry[];
};

type Props = {
    snapshot: Snapshot;
};

export default function QueueDisplay({ snapshot: initial }: Props) {
    const [snapshot, setSnapshot] = useState<Snapshot>(initial);

    useEffect(() => {
        const channel = window.Echo.channel('queue');

        channel.listen('.queue.updated', (data: { payload: Snapshot }) => {
            if (data.payload) {
                setSnapshot(data.payload);
            }
        });

        return () => {
            window.Echo.leaveChannel('queue');
        };
    }, []);

    const { current, entries } = snapshot;
    const waiting = entries.filter((entry) => entry.status === 'pending');
    const called = entries.filter((entry) => entry.status === 'called');

    return (
        <>
            <Head title="Antrian" />
            <div className="flex min-h-screen flex-col items-center justify-center bg-slate-950 p-6 text-white">
                <div className="w-full max-w-3xl text-center">
                    <p className="mb-2 text-sm uppercase tracking-[0.3em] text-slate-400">
                        Nomor Antrian Sekarang
                    </p>

                    <div className="mb-8 rounded-3xl border border-slate-700 bg-slate-900 px-12 py-10 shadow-2xl">
                        <span className="text-8xl font-black tracking-tight text-emerald-400 sm:text-9xl">
                            {current ?? '---'}
                        </span>
                    </div>

                    {waiting.length > 0 && (
                        <div className="mb-6">
                            <p className="mb-3 text-sm uppercase tracking-widest text-slate-400">
                                Menunggu
                            </p>
                            <div className="flex flex-wrap justify-center gap-2">
                                {waiting.slice(0, 8).map((entry) => (
                                    <span
                                        key={entry.id}
                                        className="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-xl font-semibold text-slate-200"
                                    >
                                        {entry.code}
                                    </span>
                                ))}
                                {waiting.length > 8 && (
                                    <span className="rounded-lg border border-slate-700 bg-slate-800 px-4 py-2 text-xl font-semibold text-slate-400">
                                        +{waiting.length - 8} lagi
                                    </span>
                                )}
                            </div>
                        </div>
                    )}

                    {called.length > 0 && (
                        <div className="flex flex-wrap justify-center gap-2">
                            {called.slice(-6).map((entry) => (
                                <span
                                    key={entry.id}
                                    className={cn(
                                        'rounded-lg border border-slate-800 bg-slate-900 px-3 py-1 text-sm font-medium text-slate-500 line-through',
                                    )}
                                >
                                    {entry.code}
                                </span>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
