FROM node:20-alpine

WORKDIR /app

# Install dependencies (cached layer)
COPY package*.json ./
RUN npm ci --prefer-offline

# Copy source
COPY . .

EXPOSE 3000

# Dev server - npm install only runs if node_modules is empty (volume mount)
CMD ["sh", "-c", "[ -d node_modules ] && [ \"$(ls -A node_modules 2>/dev/null)\" ] || npm install; npm run dev -- --host 0.0.0.0 --port 3000"]
