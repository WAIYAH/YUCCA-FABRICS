import { and, count, eq, inArray } from 'drizzle-orm';
import { db, schema } from '~/lib/db';

type WaitUntil = (promise: Promise<unknown>) => void;

/**
 * Called once a class payment is confirmed. The booking is confirmed only if it still holds all its hours
 * (a hold that expired before payment may have lost its slot to someone else).
 */
export async function onTrainingPaid(bookingId: number, _waitUntil: WaitUntil) {
  const [booking] = await db.select().from(schema.trainingBookings).where(eq(schema.trainingBookings.id, bookingId));
  if (!booking || booking.status === 'confirmed' || booking.status === 'completed') return;
  const [held] = await db.select({ n: count() }).from(schema.trainingBookingHours).where(eq(schema.trainingBookingHours.bookingId, bookingId));
  if ((held?.n ?? 0) !== booking.hours) {
    console.error('Class paid but its slot is no longer held', { bookingId, reference: booking.reference });
    return;
  }
  await db.update(schema.trainingBookings).set({ status: 'confirmed', holdExpiresAt: null })
    .where(and(eq(schema.trainingBookings.id, bookingId), inArray(schema.trainingBookings.status, ['pending_payment', 'expired'])));
}
