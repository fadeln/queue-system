import { Head } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { ChevronRight, Users } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { queue as boardRoute } from '@/routes/admin';

type Stats = {
    current: string | null;
    waiting: number;
    servedToday: number;
};

type Props = {
    stats: Stats;
};

export default function Dashboard({ stats: initial }: Props) {
    const [stats, setStats] = useState<Stats>(initial);

    useEffect(() => {
        const channel = window.Echo.channel('queue');

        channel.listen('.queue.updated', () => {
            window.location.reload();
        });

        return () => {
            window.Echo.leaveChannel('queue');
        };
    }, []);

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardDescription>Nomor sedang dilayani</CardDescription>
                            <CardTitle className="text-5xl font-black text-emerald-600">
                                {stats.current ?? '—'}
                            </CardTitle>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardDescription className="flex items-center gap-1">
                                <Users /> Menunggu
                            </CardDescription>
                            <CardTitle className="text-5xl font-black">
                                {stats.waiting}
                            </CardTitle>
                        </CardHeader>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardDescription>Dilayani hari ini</CardDescription>
                            <CardTitle className="text-5xl font-black">
                                {stats.servedToday}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <Card>
                    <CardContent className="flex items-center justify-between py-6">
                        <div>
                            <CardTitle>Kelola Antrian</CardTitle>
                            <CardDescription className="mt-1">
                                Panggil nomor berikutnya, atau tandai selesai.
                            </CardDescription>
                        </div>
                        <Button asChild>
                            <a href={boardRoute().url}>
                                Buka Antrian <ChevronRight />
                            </a>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
