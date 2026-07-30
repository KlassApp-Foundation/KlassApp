import Vue, { configureCompat } from 'vue';

// MODE 2 + suppress only proven third-party / intentional-boot noise (see knowledge 1b.5 dispositions).
// Do NOT globally suppress COMPONENT_V_MODEL — app-owned v-model debt must stay visible.
configureCompat({
    MODE: 2,
    GLOBAL_EXTEND: 'suppress-warning',
    GLOBAL_MOUNT: 'suppress-warning',
    GLOBAL_PROTOTYPE: 'suppress-warning',
    RENDER_FUNCTION: 'suppress-warning',
    INSTANCE_SET: 'suppress-warning',
    OPTIONS_BEFORE_DESTROY: 'suppress-warning',
    INSTANCE_SCOPED_SLOTS: 'suppress-warning',
    CONFIG_WHITESPACE: 'suppress-warning',
});

// Runtime compiler (inline / third-party templates): explicit whitespace silences CONFIG_WHITESPACE.
Vue.config.compilerOptions = Object.assign({}, Vue.config.compilerOptions || {}, {
    whitespace: 'preserve',
});

export default Vue;
export { configureCompat };
