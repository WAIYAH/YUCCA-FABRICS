import { emailBody, sendEmail } from '~/lib/email';
import { DEFAULT_EMAIL, getSettings, setting } from '~/lib/settings';

/** Emails the business inbox (the business_email setting). */
export async function emailBusiness(locals: App.Locals, subject: string, lines: (string | null | undefined | false)[], replyTo?: string) {
  const settings = await getSettings(locals);
  return sendEmail({
    to: setting(settings, 'business_email', DEFAULT_EMAIL),
    subject,
    text: emailBody(...lines.filter((line): line is string => Boolean(line))),
    replyTo,
  });
}
