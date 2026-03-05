module.exports = {
  apps: [
    {
      name: 'xskt',
      script: 'artisan',
      args: 'queue:work --queue=xskt --sleep=1 --timeout=600 --tries=3',
      interpreter: 'php',
      instances: 3,
      watch: false,
    }
  ]
};
