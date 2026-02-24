import { resolve, sep } from 'node:path'
import invariant from 'tiny-invariant'
import { type Plugin, type ViteDevServer } from 'vite'
import { writeThemeToFile } from '../scripts/css-utils/create-theme.ts'

export default function themePlugin(options: {
	tokensGlob: string
	outputPath: string
	tokensDir: string
}): Plugin {
	const tokensGlob = options.tokensGlob
	const outputPath = options.outputPath
	const tokensDir = options.tokensDir
	let root = ''

	return {
		name: 'theme-auto-generate',
		configResolved(config) {
			root = config.root
		},
		async buildStart() {
			invariant(tokensGlob, 'tokensGlob must be provided via vite config')
			invariant(outputPath, 'outputPath must be provided via vite config')
			const finalOutput = resolve(root, outputPath)
			await writeThemeToFile({ outputPath: finalOutput, tokensDir: resolve(root, tokensDir) })
		},
		configureServer(server: ViteDevServer) {
			invariant(tokensGlob, 'tokensGlob must be provided via vite config')
			const absGlob = resolve(root, tokensGlob)
			const tokensDirAbs = resolve(root, tokensDir)
			const finalOutput = resolve(root, outputPath)

			server.watcher.add(absGlob)

			let running = false
			let pending = false
			const trigger = async (filePath?: string) => {
				if (filePath === finalOutput) return

				if (running) {
					pending = true
					return
				}
				running = true
				try {
					const result = await writeThemeToFile({
						outputPath: finalOutput,
						tokensDir: tokensDirAbs,
					})
					if (result.written) {
						server.config.logger.info(
							`theme.css regenerated from tokens`,
						)
					}
				} catch (e) {
					server.config.logger.error(
						`Theme generation failed: ${e instanceof Error ? e.message : String(e)}`,
					)
				} finally {
					running = false
					if (pending) {
						pending = false
						void trigger().catch(() => {})
					}
				}
			}

			const shouldHandle = (filePath?: string) => {
				if (!filePath) return true
				const inTokensDir = filePath.startsWith(tokensDirAbs + sep)
				const isJson = filePath.endsWith('.json')
				return inTokensDir && isJson
			}

			server.watcher
				.on('add', (filePath) => {
					if (shouldHandle(filePath)) void trigger(filePath).catch(() => {})
				})
				.on('change', (filePath) => {
					if (shouldHandle(filePath)) void trigger(filePath).catch(() => {})
				})
				.on('unlink', (filePath) => {
					if (shouldHandle(filePath)) void trigger(filePath).catch(() => {})
				})

			void trigger().catch(() => {})
		},
	}
}
