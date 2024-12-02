<template>
  <div>
    <table>
      <thead>
        <tr>
          <th scope="col">Provider</th>
          <th v-for="feature in features" :key="feature" scope="col">
            {{ feature }}
          </th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="provider in providers" :key="provider.name">
          <th scope="row">{{ provider.name }}</th>
          <td v-for="feature in features" :key="feature">
            <div class="flex justify-center">
              <svg
                v-if="provider[feature.toLowerCase()] === 'supported'"
                class="w-6 h-6"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M20 6L9 17L4 12"
                  stroke="green"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
              <svg
                v-else-if="provider[feature.toLowerCase()] === 'planned'"
                class="w-6 h-6"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <circle
                  cx="12"
                  cy="12"
                  r="8"
                  stroke="orange"
                  stroke-width="2"
                />
              </svg>
              <svg
                v-else
                class="w-6 h-6"
                viewBox="0 0 24 24"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
              >
                <path
                  d="M18 6L6 18M6 6l12 12"
                  stroke="red"
                  stroke-width="2"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                />
              </svg>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <div class="flex gap-4 mt-4">
      <div class="flex items-center gap-2">
        <svg
          class="w-5 h-5"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M20 6L9 17L4 12"
            stroke="green"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        <span>Supported</span>
      </div>
      <div class="flex items-center gap-2">
        <svg
          class="w-5 h-5"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <circle cx="12" cy="12" r="8" stroke="orange" stroke-width="2" />
        </svg>
        <span>Planned Support</span>
      </div>
      <div class="flex items-center gap-2">
        <svg
          class="w-5 h-5"
          viewBox="0 0 24 24"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
        >
          <path
            d="M18 6L6 18M6 6l12 12"
            stroke="red"
            stroke-width="2"
            stroke-linecap="round"
            stroke-linejoin="round"
          />
        </svg>
        <span>Unsupported</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "CompatibilityMatrix",
  data() {
    return {
      features: ["Text", "Structured", "Embeddings", "Image", "Tools"],
      providers: [
        {
          name: "Amazon Bedrock",
          text: "planned",
          structured: "planned",
          embeddings: "planned",
          image: "planned",
          tools: "planned",
        },
        {
          name: "Anthropic",
          text: "supported",
          structured: "planned",
          embeddings: "unsupported",
          image: "supported",
          tools: "supported",
        },
        {
          name: "Gemini",
          text: "planned",
          structured: "planned",
          embeddings: "planned",
          image: "planned",
          tools: "planned",
        },
        {
          name: "Groq",
          text: "supported",
          structured: "planned",
          embeddings: "planned",
          image: "supported",
          tools: "supported",
        },
        {
          name: "Mistral",
          text: "supported",
          structured: "planned",
          embeddings: "supported",
          image: "supported",
          tools: "supported",
        },
        {
          name: "Ollama",
          text: "supported",
          structured: "planned",
          embeddings: "supported",
          image: "supported",
          tools: "supported",
        },
        {
          name: "OpenAI",
          text: "supported",
          structured: "supported",
          embeddings: "supported",
          image: "supported",
          tools: "supported",
        },
        {
          name: "xAI",
          text: "supported",
          structured: "planned",
          embeddings: "unsupported",
          image: "supported",
          tools: "supported",
        },
      ],
    };
  },
};
</script>
