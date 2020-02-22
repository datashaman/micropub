const fs = require('fs')
const glob = require('glob')

let posts = []

glob('/home/marlinf/Desktop/samples/*.mf2.json', (err, files) => {
    files.forEach(file => {
        const data = JSON.parse(fs.readFileSync(file))
        posts.push(data)
    })

    console.log(JSON.stringify(posts, null, 2))
})
