module.exports = {
  plugins: ['security'],
  extends: ['plugin:security/recommended'],
  rules: {
    'security/detect-object-injection': 'warn',
    'security/detect-non-literal-require': 'error',
    'security/detect-non-literal-fs-filename': 'error',
    'security/detect-eval-with-expression': 'error',
    'security/detect-unsafe-regex': 'error',
  },
};
