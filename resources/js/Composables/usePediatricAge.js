/**
 * Shared composable for pediatric age calculations.
 * Used across Dashboard, WellChild, Patients/Show, etc.
 */
export function getChildAge(dob, isRtl = false) {
    if (!dob) return '--';
    const birth = new Date(dob);
    const now = new Date();

    let months = (now.getFullYear() - birth.getFullYear()) * 12 + (now.getMonth() - birth.getMonth());
    if (now.getDate() < birth.getDate()) months--;
    if (months < 0) months = 0;

    const years = Math.floor(months / 12);
    const remainingMonths = months % 12;

    if (isRtl) {
        if (months < 1) {
            const days = Math.floor((now - birth) / (1000 * 60 * 60 * 24));
            return `${days} يوم`;
        }
        if (months < 24) return `${months} شهر`;
        return `${years === 2 ? 'عامين' : years + ' سنوات'} و ${remainingMonths} ${remainingMonths <= 2 ? 'شهر' : 'أشهر'}`;
    }

    if (months < 1) {
        const days = Math.floor((now - birth) / (1000 * 60 * 60 * 24));
        return `${days} day${days !== 1 ? 's' : ''}`;
    }
    if (months < 24) return `${months} month${months !== 1 ? 's' : ''}`;
    return `${years}y ${remainingMonths}m`;
}

/**
 * Get a detailed bilingual age display from ageMonths and ageDays props.
 */
export function getAgeDisplay(ageMonths, ageDays) {
    const totalMonths = ageMonths || 0;
    const years = Math.floor(totalMonths / 12);
    const months = totalMonths % 12;

    if (totalMonths < 24) {
        return {
            en: `${totalMonths} month${totalMonths !== 1 ? 's' : ''}${ageDays ? ` & ${ageDays} days` : ''}`,
            ar: `${totalMonths} شهر${ageDays ? ` و ${ageDays} يوم` : ''}`,
        };
    }
    return {
        en: `${years} year${years !== 1 ? 's' : ''} & ${months} month${months !== 1 ? 's' : ''}`,
        ar: `${years === 2 ? 'عامين' : years + ' سنوات'} و ${months} ${months <= 2 ? 'شهر' : 'أشهر'}`,
    };
}
