# Use an official Node.js image as the base
FROM node:16

# Set the working directory
WORKDIR /app

# Copy package.json and package-lock.json files
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the application files
COPY . .

# Expose the WebSocket port
EXPOSE 4000

# Start the WebSocket server
CMD ["node", "index.js"]
