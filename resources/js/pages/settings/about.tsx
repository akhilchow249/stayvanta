import { Head } from '@inertiajs/react';
import {
    BellRing,
    Building2,
    CreditCard,
    ClipboardList,
    Users,
    Wrench,
} from 'lucide-react';

import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { t } from '@/lib/i18n';

const features = [
    {
        icon: Building2,
        title: 'Property Management',
        description:
            'Manage PG properties, rooms, units, amenities, and occupancy from one place.',
    },
    {
        icon: Users,
        title: 'Tenant Management',
        description:
            'Keep tenant profiles, leases, occupancy details, and relationships organized.',
    },
    {
        icon: ClipboardList,
        title: 'Rent & Billing',
        description:
            'Create invoices, track outstanding balances, and keep rent records organized.',
    },
    {
        icon: CreditCard,
        title: 'Online Payments',
        description:
            'Let tenants pay rent online and track payment activity securely.',
    },
    {
        icon: Wrench,
        title: 'Maintenance',
        description:
            'Manage maintenance requests and keep property issues visible and organized.',
    },
    {
        icon: BellRing,
        title: 'Notifications',
        description:
            'Keep tenants and property managers informed with timely reminders and updates.',
    },
];

export default function About() {
    return (
        <div className="space-y-6">
            <Head title={t('About StayVanta')} />

            <div>
                <h2 className="text-lg font-medium">
                    {t('About StayVanta')}
                </h2>

                <p className="mt-1 text-sm text-muted-foreground">
                    {t(
                        'A modern SaaS platform built to simplify PG and property management.',
                    )}
                </p>
            </div>

            <Card>
                <CardHeader className="space-y-4">
                    <div className="flex items-center gap-3">
                        <div className="flex size-12 items-center justify-center rounded-xl bg-primary/10">
                            <Building2
                                className="size-6 text-primary"
                                aria-hidden="true"
                            />
                        </div>

                        <div>
                            <CardTitle className="text-2xl">
                                StayVanta
                            </CardTitle>

                            <CardDescription className="mt-1">
                                Manage Stays. Automate Rent. Grow Smarter.
                            </CardDescription>
                        </div>
                    </div>
                </CardHeader>

                <CardContent className="space-y-4 text-sm leading-6 text-muted-foreground">
                    <p>
                        StayVanta is a SaaS platform designed for PG owners
                        and property managers to manage their properties,
                        tenants, leases, billing, payments, maintenance, and
                        day-to-day operations from one place.
                    </p>

                    <p>
                        Our goal is to reduce manual work, make rent
                        collection easier, improve tenant communication, and
                        give property managers a clear view of their business.
                    </p>
                </CardContent>
            </Card>

            <div>
                <h3 className="text-base font-medium">
                    {t('What StayVanta helps you manage')}
                </h3>

                <p className="mt-1 text-sm text-muted-foreground">
                    {t(
                        'Everything you need to run and manage a modern PG operation.',
                    )}
                </p>
            </div>

            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {features.map((feature) => {
                    const Icon = feature.icon;

                    return (
                        <Card key={feature.title}>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <Icon
                                        className="size-4 text-primary"
                                        aria-hidden="true"
                                    />
                                    {t(feature.title)}
                                </CardTitle>
                            </CardHeader>

                            <CardContent>
                                <p className="text-sm leading-6 text-muted-foreground">
                                    {t(feature.description)}
                                </p>
                            </CardContent>
                        </Card>
                    );
                })}
            </div>

            <Card>
                <CardContent className="pt-6">
                    <p className="text-center text-sm font-medium">
                        {t(
                            'Built to make PG management simpler, faster, and more organized.',
                        )}
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}