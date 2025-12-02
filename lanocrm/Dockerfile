FROM node:20-alpine

WORKDIR /app

# Install dependencies first for better cache
COPY package*.json ./
RUN npm install

# Copy source
COPY . .

EXPOSE 3000

# Use shell form to allow variable expansion if needed, but exec form is preferred for signal handling
# However, for dev server with npm install, shell form is practical
CMD ["sh", "-c", "npm install && npm run dev -- --host 0.0.0.0 --port 3000"]
