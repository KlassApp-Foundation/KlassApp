/**
 * Set jQuery on window before Bootstrap 4's side-effect import runs.
 * ESM hoists imports in a single module, so window.$ must be assigned in a
 * dependency that finishes evaluating before `import 'bootstrap'`.
 */
import $ from 'jquery';

window.$ = window.jQuery = $;

export default $;
