import Vue from 'vue'

/*
  Валидация обязательных полей
*/
const validateAppConfig = (config) => {
  const requiredFields = ['token', 'baseRoute', 'baseUrl']

  const keys = Object.keys(config)
  const values = Object.values(config)

  if (requiredFields.every(key => keys.includes(key)) &&
      values.every(value => !!value)) {
    return true
  }

  return false
}

// читает первую строку из blob
class FirstLineReader {
  constructor () {
    this.events = {}
    this.chunkSize = 512
    this.readPos = 0
    this.reader = new FileReader()
    this.lines = []
    this.chunk = ''
    this.file = null

    this.reader.onload = () => {
      this.chunk += this.reader.result
      this.process()
    }
  }

  /*
    Remove non printable characters
  */
  _fixString (str) {
    return str.replace(/\uFFFD/g, '')
  }

  on (event, cb) {
    this.events[event] = cb
  }

  _emit (event, args) {
    if (typeof this.events[event] === 'function') {
      this.events[event].apply(this, args)
    }
  }

  process () {
    if (/\n/.test(this.chunk)) {
      let lines = this.chunk.split('\n')
      let line = this._fixString(lines[0])
      console.log(line)
      this._emit('line', [line])
    }
    else {
      if (this.readPos < this.file.size) {
        this.step()
      }
      else {
        this._emit('error')
      }
    }
  }

  read (file, encoding) {
    this.file = file
    this.lines = []
    this.chunk = ''
    this.readPos = 0
    this.encoding = encoding || 'UTF8'

    this.step()
  }

  step () {
    let blob = this.file.slice(
      this.readPos,
      this.readPos + this.chunkSize
    )

    this.readPos += this.chunkSize

    this.reader.readAsText(blob, this.encoding)
  }
}

const parseCsvHeaders = (raw, delimiter = ';') => {
  let clean = raw.replace(/(\r\n|\n)/gm, '')
  clean = clean.split('"').join('')
  return clean.split(delimiter)
}

const submitData = async function (url, { data, profile }) {
  let request = new FormData()
  request.append('csv-file', data.csvFileRef.files[0])

  if (data.imagesZipFileRef && data.imagesZipFileRef.files.length > 0) {
    request.append('images-zip', data.imagesZipFileRef.files[0])
  }

  request.append('profile-json', JSON.stringify(profile))

  return Vue.http.post(url, request, {
    headers: {
      'Content-Type': 'multipart/form-data'
    }
  })
}

const validateProfile = (profile) => {
  // validate key field
  return profile.keyField && profile.bindings.findIndex(val => val === profile.keyField) !== -1
}

const capitalizeFirstLetter = (str) => {
  return str.charAt(0).toUpperCase() + str.slice(1)
}

const normalizeNamespace = (namespace = '') => {
  if (namespace.length > 0) {
    if (namespace.charAt(namespace.length - 1) !== '/') {
      namespace += '/'
    }
  }

  return namespace
}

// code from Vuex source
const normalizeMap = (map) => {
  return Array.isArray(map)
    ? map.map(function (key) { return ({ key: key, val: key }) })
    : Object.keys(map).map(function (key) { return ({ key: key, val: map[key] }) })
}

const mapVuexModels = (models, namespace = '') => {
  namespace = normalizeNamespace(namespace)
  models = normalizeMap(models)

  return models.reduce(function (prev, {key, val}) {
    prev[key] = {
      get () {
        let value = null
        try {
          value = this.$store.getters[`${namespace}${val}`]
        }
        catch (e) {
          console.error(e)
          console.error(`missing getter ${namespace}${val}`)
        }
        return value
      },
      set (value) {
        this.$store.dispatch(`${namespace}set${capitalizeFirstLetter(val)}`, value)
      }
    }

    return prev
  }, {})
}

const toMutationName = (name) => {
  let parts = name.match(/([A-Z]?[^A-Z]*)/g).slice(0, -1)
  return parts.join('_').toUpperCase()
}

const genStoreFields = (fields, storeAttr) => {
  let result = {
    getters: {},
    actions: {},
    mutations: {}
  }

  fields.reduce(function (prev, field) {
    prev.getters[field] = function (state) {
      return state[storeAttr][field]
    }
    prev.actions[`set${field.charAt(0).toUpperCase() + field.substr(1)}`] = function ({ commit }, data) {
      commit(toMutationName(field), data)
    }
    prev.mutations[toMutationName(field)] = function (state, value) {
      Vue.set(state[storeAttr], field, value)
    }

    return prev
  }, result)

  return result
}

export {
  mapVuexModels,
  validateAppConfig,
  FirstLineReader,
  parseCsvHeaders,
  submitData,
  validateProfile,
  genStoreFields
}
