import modules from './modules'

class ApiProvider {
  constructor (modules) {
    this.modules = modules
    this._prepared = false
  }

  prepare (options) {
    if (!this._prepared) {
      this._config = options

      let proxied = {
        mkUrl: this.mkUrl.bind(this)
      }

      let modules = this.modules

      Object.keys(modules).forEach(type => {
        proxied[type] = {}

        Object.keys(modules[type]).forEach(method => {
          let moduleCallback = this._typedMkUrl(type)
          proxied[type][method] = (options) => modules[type][method](moduleCallback, options)
        })
      })

      this.proxy = proxied
      this._prepared = true

      return this
    }
    else {
      throw new Error('Trying to prepare already prepared $api!')
    }
  }

  install (Vue, options) {
    if (!this._prepared) {
      this.prepare(options)
    }

    Vue.$api = this.proxy

    Vue.mixin({
      created () {
        this.$api = Vue.$api
      }
    })
  }

  _typedMkUrl (type) {
    return (action, params) => {
      return this.mkUrl(action, { ...params, type })
    }
  }

  mkUrl (action, getParams = {}) {
    let getString = Object.keys(getParams)
      .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(getParams[key]))
      .join('&')

    if (getString.length > 0) {
      getString = `&${getString}`
    }

    return `${this._config.baseUrl}${this._config.baseRoute}/${action}&${this._config.tokenName}=${this._config.token}${getString}`
  }
}

const provider = new ApiProvider(modules)
export default provider
