export type TokensDir = string

import { readFileSync } from 'node:fs'
import { join, dirname } from 'node:path'
import { fileURLToPath } from 'node:url'
import type * as z from 'zod'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

function loadJson<T = unknown>(tokensDir: TokensDir, fileName: string): T {
	const filePath = join(tokensDir, fileName)
	return JSON.parse(readFileSync(filePath, 'utf8')) as T
}

export function getDefaultTokensDir(): string {
	return join(__dirname, '..', '..', 'resources', 'design-tokens')
}

export function parseFile<S extends z.ZodTypeAny>(
	schema: S,
	tokensDir: TokensDir,
	fileName: string,
): z.infer<S> {
	try {
		return schema.parse(loadJson(tokensDir, fileName))
	} catch (e) {
		if (e instanceof Error && 'issues' in e) {
			throw new Error(`Invalid token file: ${fileName}\n\n${e.message}`)
		}
		throw e
	}
}
