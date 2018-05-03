export default class UrlStore {
  constructor (config) {
    this._config = config
  }

  actionUrl (action) {
    return `${this._config.baseUrl}${this._config.baseRoute}/${action}&${this._config.tokenName}=${this._config.token}`
  }
}
