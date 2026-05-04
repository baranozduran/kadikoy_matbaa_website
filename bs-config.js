module.exports = {
  proxy: "http://localhost/myproject", // This points to your Laragon server
  files: ["**/*.php", "**/*.css", "**/*.js"], // Files to watch
  reloadDelay: 1000,
  ui: {
    port: 3001
  },
  port: 3000
};