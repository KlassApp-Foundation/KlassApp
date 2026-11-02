export const COUNTRY_CHANGE_MESSAGE =
    "Confirm the country change. It affects phone formats and curriculum defaults.";

/**
 * A failed school-details save keeps the values the admin typed.
 * The country sentence is returned only when the server sent it for a real
 * unconfirmed country change. Every other failure keeps its own message.
 *
 * @param {{ status?: number, data?: { errors?: object, message?: string } } | null | undefined} response
 * @param {boolean} countryChangePending
 * @returns {{ errors: Record<string, string[]>, keepEnteredValues: true } | null}
 */
export function countrySaveFailure(response, countryChangePending) {
    if (response && response.status < 400) {
        return null;
    }

    const errors = { ...(response?.data?.errors || {}) };
    if (!countryChangePending) {
        delete errors.confirm_country_change;
    }
    if (Object.keys(errors).length > 0) {
        return { errors, keepEnteredValues: true };
    }

    const message = response?.data?.message;
    const text =
        typeof message === "string" && message.trim() !== ""
            ? message.trim()
            : "Could not save (HTTP " +
              (response && response.status ? response.status : "network") +
              "). Nothing was changed.";

    return { errors: { save: [text] }, keepEnteredValues: true };
}
