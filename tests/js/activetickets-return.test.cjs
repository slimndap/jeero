const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const script = fs.readFileSync(path.join(__dirname, '../../assets/js/theaters/Activetickets.js'), 'utf8');

function run({ widget = 'account', vendor = true, topLevel = false, crossOrigin = false, noFrame = false, blocked = false, destination = 'https://venue.example/account/?saved=1#details' } = {}) {
    const navigations = [];
    const window = {
        location: { origin: 'https://venue.example', href: destination },
        frameElement: noFrame ? null : {
            matches: selector => ['account', 'cart', 'tickets'].includes(widget) && selector.includes(`--${widget}-inline iframe`),
            closest: selector => vendor && selector === '.jeero-theater-widget--theater-activetickets',
        },
    };
    window.self = window;
    window.top = topLevel ? window : {
        location: {
            get origin() {
                if (crossOrigin) throw new Error('SecurityError');
                return 'https://venue.example';
            },
            replace(url) {
                if (blocked) throw new Error('SecurityError');
                navigations.push(url);
            },
        },
    };
    vm.runInNewContext(script, { window, document: { readyState: 'loading', addEventListener() {} } });
    return { navigations, window };
}

for (const widget of ['account', 'cart', 'tickets']) {
    test(`promotes a returned page inside the ${widget} widget and preserves its URL`, () => {
        const result = run({ widget });
        assert.deepEqual(result.navigations, ['https://venue.example/account/?saved=1#details']);
        assert.equal(result.window.onmessage, undefined);
    });
}

test('also promotes a return to the homepage', () => {
    assert.deepEqual(run({ destination: 'https://venue.example/' }).navigations, ['https://venue.example/']);
});

for (const options of [{ topLevel: true }, { widget: 'ordinary' }, { vendor: false }, { crossOrigin: true }, { noFrame: true }, { blocked: true }]) {
    test(`leaves unrelated or inaccessible embeddings alone: ${JSON.stringify(options)}`, () => {
        const result = run(options);
        assert.deepEqual(result.navigations, []);
        assert.equal(typeof result.window.onmessage, 'function');
    });
}
