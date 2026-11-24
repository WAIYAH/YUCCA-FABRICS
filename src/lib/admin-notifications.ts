import { and, count, eq, gte, isNull, ne } from 'drizzle-orm';
import { db, schema } from '~/lib/db';
import { todayInNairobi } from '~/lib/format';

export interface AdminNotification {
  icon: string;
  label: string;
  count: number;
  href: string;
}

/** Work waiting for staff, shown in the admin header bell. One D1 round trip; only non-zero items are returned. */
export async function adminNotifications(): Promise<AdminNotification[]> {
  const o = schema.orders;
  const t = schema.trainingBookings;
  const c = schema.consultations;
  const [[orders], [classes], [consultations], [requests], [messages], [reviews]] = await db.batch([
    db.select({ n: count() }).from(o).where(eq(o.status, 'pending')),
    db.select({ n: count() }).from(t).where(and(eq(t.status, 'confirmed'), gte(t.startsAt, new Date()), isNull(t.meetingUrl), ne(t.platform, 'whatsapp'))),
    db.select({ n: count() }).from(c).where(and(eq(c.status, 'pending'), gte(c.preferredDate, todayInNairobi()))),
    db.select({ n: count() }).from(schema.serviceRequests).where(eq(schema.serviceRequests.status, 'new')),
    db.select({ n: count() }).from(schema.contacts).where(eq(schema.contacts.status, 'new')),
    db.select({ n: count() }).from(schema.productReviews).where(eq(schema.productReviews.status, 'pending')),
  ]);
  const items: AdminNotification[] = [
    { icon: 'fa6-solid:bag-shopping', label: 'Pending orders', count: orders?.n ?? 0, href: '/admin/orders?status=pending' },
    { icon: 'fa6-solid:video', label: 'Classes needing a meeting link', count: classes?.n ?? 0, href: '/admin/training' },
    { icon: 'fa6-solid:calendar-check', label: 'Consultations to approve', count: consultations?.n ?? 0, href: '/admin/consultations?status=pending' },
    { icon: 'fa6-solid:couch', label: 'New service requests', count: requests?.n ?? 0, href: '/admin/requests' },
    { icon: 'fa6-solid:inbox', label: 'New messages', count: messages?.n ?? 0, href: '/admin/messages' },
    { icon: 'fa6-solid:star', label: 'Reviews to moderate', count: reviews?.n ?? 0, href: '/admin/reviews' },
  ];
  return items.filter((item) => item.count > 0);
}
