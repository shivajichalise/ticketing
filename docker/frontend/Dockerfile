FROM node:22.15.0

WORKDIR /app

# Install pnpm globally
RUN npm install -g pnpm

# Copy manifest and lockfile before install (very important!)
COPY package.json pnpm-lock.yaml ./

# Install dependencies (includes vite + devDeps)
RUN pnpm install --frozen-lockfile

# Copy the rest of the app
COPY . .

# Start Vite dev server
CMD ["pnpm", "dev"]
