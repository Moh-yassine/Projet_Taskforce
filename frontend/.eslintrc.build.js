module.exports = {
  extends: [
    '@vue/eslint-config-typescript',
    '@vue/eslint-config-prettier'
  ],
  rules: {
    // Désactiver les règles strictes pour le build de production
    '@typescript-eslint/no-explicit-any': 'off',
    '@typescript-eslint/no-unused-vars': 'off',
    '@typescript-eslint/no-non-null-assertion': 'off',
    'vue/multi-word-component-names': 'off',
    'no-console': 'off',
    'no-debugger': 'off'
  },
  env: {
    node: true,
    browser: true,
    es2022: true
  }
}
