import assert from "node:assert/strict";
import test from "node:test";
import {
    COUNTRY_CHANGE_MESSAGE,
    countrySaveFailure,
} from "../../resources/assets/js/components/schooldetail/countrySaveError.mjs";

test("a network or server failure does not show the country sentence", () => {
    const network = countrySaveFailure(undefined, false);
    assert.equal(network.keepEnteredValues, true);
    assert.deepEqual(network.errors, {
        save: ["Could not save (HTTP network). Nothing was changed."],
    });
    assert.equal(network.errors.confirm_country_change, undefined);

    const server = countrySaveFailure(
        { status: 500, data: { message: "The motto could not be stored.", errors: { save: ["The motto could not be stored."] } } },
        true,
    );
    assert.deepEqual(server.errors, { save: ["The motto could not be stored."] });
    assert.equal(server.errors.confirm_country_change, undefined);
    assert.equal(server.keepEnteredValues, true);
});

test("the country sentence is kept only for an unconfirmed country change", () => {
    const unconfirmed = countrySaveFailure(
        {
            status: 422,
            data: { errors: { confirm_country_change: [COUNTRY_CHANGE_MESSAGE] } },
        },
        true,
    );
    assert.deepEqual(unconfirmed.errors.confirm_country_change, [COUNTRY_CHANGE_MESSAGE]);
    assert.equal(unconfirmed.keepEnteredValues, true);

    const notChanging = countrySaveFailure(
        {
            status: 422,
            data: { errors: { confirm_country_change: [COUNTRY_CHANGE_MESSAGE] } },
        },
        false,
    );
    assert.equal(notChanging.errors.confirm_country_change, undefined);
    assert.ok(notChanging.errors.save);
});

test("a successful response is not turned into an error and does not reset the form", () => {
    assert.equal(countrySaveFailure({ status: 200, data: {} }, true), null);
});
