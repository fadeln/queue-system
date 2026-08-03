import { Head, router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ChevronLeft, ChevronRight, Plus, RotateCcw, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { called, cancel, next, prev, reset, store } from '@/routes/admin/queue';
import { queue as boardRoute } from '@/routes/admin';

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

const statusLabel: Record<QueueEntry['status'], string> = {
    pending: 'Menunggu',
    calling: 'Dipanggil',
    called: 'Selesai',
    skipped: 'Dilewati',
};

export default function QueueBoard({ snapshot: initial }: Props) {
    const [snapshot, setSnapshot] = useState<Snapshot>(initial);
    const [open, setOpen] = useState(false);
    const [cancelling, setCancelling] = useState<QueueEntry | null>(null);

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

    const nextCode = `A-${String(
        Math.max(0, ...entries.map((entry) => entry.position)) + 1,
    ).padStart(3, '0')}`;

    return (
        <>
            <Head title="Kelola Antrian" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-semibold">Kelola Antrian</h2>
                        <p className="text-sm text-muted-foreground">
                            Nomor sedang dilayani:{' '}
                            <span className="text-lg font-bold text-primary">
                                {current ?? '—'}
                            </span>
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            size="sm"
                            onClick={() => setOpen(true)}
                        >
                            <Plus /> Tambah
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.post(reset().url)}
                        >
                            <RotateCcw /> Reset
                        </Button>
                    </div>
                </div>

                <div className="flex gap-4">
                    <Button
                        variant="outline"
                        size="lg"
                        className="flex-1"
                        onClick={() => router.post(prev().url)}
                    >
                        <ChevronLeft /> Sebelumnya
                    </Button>
                    <Button
                        size="lg"
                        className="flex-1"
                        onClick={() => router.post(next().url)}
                    >
                        Berikutnya <ChevronRight />
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Antrian</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul className="divide-y divide-border">
                            {entries.map((entry) => (
                                <li
                                    key={entry.id}
                                    className="flex items-center justify-between py-3"
                                >
                                    <div className="flex items-center gap-3">
                                        <span
                                            className={cn(
                                                'inline-flex h-10 w-16 items-center justify-center rounded-md border text-base font-bold',
                                                entry.status === 'calling' &&
                                                    'border-emerald-500 bg-emerald-500/10 text-emerald-600',
                                                entry.status === 'called' &&
                                                    'border-border text-muted-foreground line-through',
                                                entry.status === 'pending' &&
                                                    'border-border',
                                            )}
                                        >
                                            {entry.code}
                                        </span>
                                        <span
                                            className={cn(
                                                'text-sm font-medium',
                                                entry.status === 'called' &&
                                                    'text-muted-foreground',
                                            )}
                                        >
                                            {statusLabel[entry.status]}
                                        </span>
                                    </div>
                                    {entry.status === 'pending' && (
                                        <>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() =>
                                                    router.post(
                                                        called({ entry: entry.id }).url,
                                                    )
                                                }
                                            >
                                                Tandai selesai
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-muted-foreground hover:text-destructive"
                                                onClick={() => setCancelling(entry)}
                                                title="Batalkan antrian"
                                            >
                                                <X />
                                            </Button>
                                        </>
                                    )}
                                </li>
                            ))}
                        </ul>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tambah Antrian</DialogTitle>
                        <DialogDescription>
                            Nomor berikutnya yang akan dibuat adalah{' '}
                            <span className="font-bold text-foreground">{nextCode}</span>.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            onClick={() => {
                                setOpen(false);
                                router.post(store().url);
                            }}
                        >
                            <Plus /> Tambah
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={cancelling !== null}
                onOpenChange={(value) => {
                    if (!value) {
                        setCancelling(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Batalkan Antrian</DialogTitle>
                        <DialogDescription>
                            Batalkan nomor{' '}
                            <span className="font-bold text-foreground">
                                {cancelling?.code}
                            </span>
                            ? Antrian ini akan dilewati dan tidak akan dipanggil lagi.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setCancelling(null)}
                        >
                            Tidak
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => {
                                if (cancelling) {
                                    router.post(
                                        cancel({ entry: cancelling.id }).url,
                                    );
                                }
                                setCancelling(null);
                            }}
                        >
                            <X /> Batalkan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

QueueBoard.layout = {
    breadcrumbs: [
        {
            title: 'Kelola Antrian',
            href: boardRoute(),
        },
    ],
};
