import js from '@eslint/js';
import globals from 'globals';
import vueParser from 'vue-eslint-parser';
import vuePlugin from 'eslint-plugin-vue';

export default [
    {
        files: ['**/*.{js,mjs,cjs,jsx,mjsx,ts,tsx,mtsx}'],
        ...js.configs.recommended,
        languageOptions: {
            globals: {
                ...globals.browser,
                ...globals.node,
                ...globals.es2021
            },
            parserOptions: {
                ecmaVersion: 2021,
                sourceType: 'module'
            }
        }
    },
    {
        files: ['**/*.vue'],
        plugins: {
            vue: vuePlugin
        },
        languageOptions: {
            parser: vueParser,
            parserOptions: {
                ecmaVersion: 2021,
                sourceType: 'module'
            }
        },
        rules: {
            ...vuePlugin.configs.recommended.rules,
            'vue/multi-word-component-names': 'off',
            'vue/require-prop-types': 'warn',
            'vue/html-indent': ['error', 4],
            'no-unused-vars': 'warn',
            'vue/max-attributes-per-line': ['error', {
                singleline: 3,
                multiline: 1
            }],
            'vue/html-self-closing': ['error', {
                html: {
                    void: 'never',
                    normal: 'always',
                    component: 'always'
                }
            }],
            'no-console': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
            'no-debugger': process.env.NODE_ENV === 'production' ? 'warn' : 'off',
            'vue/no-v-html': 'off',
            'vue/require-default-prop': 'off',
            'vue/require-explicit-emits': 'off'
        }
    }
];
